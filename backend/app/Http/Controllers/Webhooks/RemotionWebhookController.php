<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Syllaby\Videos\Video;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\VideoProvider;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Videos\Jobs\ProcessVideoPublicationsJob;
use App\Syllaby\Videos\Jobs\Renders\NotifyVideoRenderJob;
use App\Syllaby\Videos\Jobs\Renders\CreateVideoRenderMediaJob;

class RemotionWebhookController extends Controller
{
    /**
     * Handle the webhook.
     */
    public function handle(Request $request): Response
    {
        if (! $video = $this->fetchVideo($request->input('renderId'))) {
            Log::error('Remotion render [{id}] not found from webhook', [
                'id' => $request->input('renderId'),
                'metadata' => $request->json('customData'),
            ]);

            $this->cleanup($request->input('renderId'));

            return $this->success();
        }

        if ($this->timeout($request->input('type'))) {
            Log::error('Remotion render {id} timed out from webhook', ['id' => $request->input('renderId')]);
            $this->markAsFailed($video);

            return $this->success();
        }

        if ($this->failed($request->input('type'))) {
            $this->markAsFailed($video);

            return $this->success();
        }

        $data = [
            'render_id' => $request->input('renderId'),
            'render_url' => $request->input('outputUrl'),
        ];

        Bus::chain([
            new CreateVideoRenderMediaJob($video, $data),
            new ProcessVideoPublicationsJob($video),
            new NotifyVideoRenderJob($video),
        ])->dispatch();

        return $this->success();
    }

    /**
     * Attempts to fetch the video object.
     */
    private function fetchVideo(string $id): ?Video
    {
        return Video::where('provider_id', $id)
            ->where('provider', VideoProvider::REMOTION->value)
            ->first();
    }

    /**
     * Checks if the render has failed.
     */
    private function failed(string $status): bool
    {
        return $status === 'error';
    }

    /**
     * Checks if the render has failed.
     */
    private function timeout(string $status): bool
    {
        return VideoStatus::TIMEOUT->value === $status;
    }

    /**
     * Marks the video as failed and refunds the user.
     */
    private function markAsFailed(Video $video): Video
    {
        Log::error('Remotion has failed to render video {id}', ['id' => $video->id]);

        $user = $video->user;

        Cache::lock('lock:remotion:fails', 5)->block(10, function () {
            $current = Cache::get('remotion:fails', 0);
            Cache::put('remotion:fails', $current + 1, now()->addMinutes(15));
        });

        return DB::transaction(function () use ($video, $user) {
            (new CreditService($user))->refund($video);

            return tap($video)->update([
                'url' => null,
                'synced_at' => null,
                'status' => VideoStatus::FAILED,
            ]);
        });
    }

    /**
     * Handle successful calls on the controller.
     */
    protected function success(): Response
    {
        return new Response('Webhook Handled', 200);
    }

    /**
     * Cleans up the render files.
     */
    private function cleanup(string $id): void
    {
        dispatch(fn () => Storage::disk('s3')->deleteDirectory("renders/{$id}"))->afterResponse();
    }
}
