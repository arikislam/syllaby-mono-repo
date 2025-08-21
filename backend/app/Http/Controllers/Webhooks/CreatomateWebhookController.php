<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Syllaby\Videos\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\VideoProvider;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Videos\Jobs\ProcessVideoPublicationsJob;
use App\Syllaby\Videos\Jobs\Renders\NotifyVideoRenderJob;
use App\Syllaby\Videos\Jobs\Renders\HandleBulkScheduledVideo;
use App\Syllaby\Videos\Jobs\Renders\CreateVideoRenderMediaJob;

class CreatomateWebhookController extends Controller
{
    /**
     * Handle a Creatomate webhook call.
     */
    public function handle(Request $request): Response
    {
        if (! $video = $this->fetchVideo($request->input('id'))) {
            Log::error('Creatomate render {id} not found from webhook', [
                'id' => $request->input('id'),
                'metadata' => json_decode($request->input('metadata'), true),
            ]);

            return $this->success();
        }

        if ($video->status === VideoStatus::COMPLETED) {
            Log::info('Creatomate render {provider_id} already completed', [
                'provider_id' => $request->input('id'),
                'video_id' => $video->id,
            ]);

            return $this->success();
        }

        if ($this->failed($request->input('status'))) {
            $this->markAsFailed($video);

            return $this->success();
        }

        $data = [
            'render_id' => $request->input('id'),
            'render_url' => $request->input('url'),
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
            ->where('provider', VideoProvider::CREATOMATE->value)
            ->first();
    }

    /**
     * Checks if the render has failed.
     */
    private function failed(string $status): bool
    {
        return VideoStatus::FAILED->value === $status;
    }

    /**
     * Marks the video as failed and refunds the user.
     */
    private function markAsFailed(Video $video): Video
    {
        Log::error('Creatomate has failed to render video footage {id}', [
            'id' => $video->id,
        ]);

        $user = $video->user;

        return DB::transaction(function () use ($video, $user) {
            (new CreditService($user))->refund($video);

            return tap($video)->update([
                'status' => VideoStatus::FAILED,
                'synced_at' => null,
                'url' => null,
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
}
