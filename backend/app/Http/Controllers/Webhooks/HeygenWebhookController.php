<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Auth\Jobs\ProcessWelcomeVideo;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Http\Middleware\VerifyHeyGenWebhookSignature;
use App\Syllaby\RealClones\Jobs\CreateRealCloneMediaJob;
use App\Syllaby\RealClones\Jobs\NotifyRealCloneGenerationJob;

/**
 *      {
 *          "video_id": "c697d05202a845329c29e1799e4e661a",
 *          "url": "<url>",
 *          "callback_id": {"source": <url-hash>, "user_id": <optional>}
 *      }
 */
class HeygenWebhookController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        if (config('services.heygen.webhook.secret')) {
            $this->middleware(VerifyHeyGenWebhookSignature::class);
        }
    }

    /**
     * Handle income event requests from HeyGen webhook.
     */
    public function handle(Request $request): Response
    {
        $input = $request->input('event_data');
        $status = $this->status($request->input('event_type'));
        $metadata = json_decode(Arr::get($input, 'callback_id'), true);

        $data = [
            'metadata' => $metadata,
            'video_url' => Arr::get($input, 'url'),
            'video_id' => Arr::get($input, 'video_id'),
        ];

        if ($this->isWelcomeVideo($metadata) && Arr::has($input, 'url')) {
            dispatch(new ProcessWelcomeVideo($data, $status));

            return $this->success();
        }

        if (!$clone = $this->fetchRealClone($id = Arr::get($input, 'video_id'))) {
            Log::warning('Real Clone video {id} not found on Heygen', ['id' => $id]);

            return $this->success();
        }

        if ($this->hasFailed($status)) {
            $this->markAsFailed($clone);

            return $this->success();
        }

        $clone = tap($clone)->update([
            'url' => Arr::get($input, 'url'),
            'status' => RealCloneStatus::SYNCING,
        ]);

        Bus::chain([
            new CreateRealCloneMediaJob($clone),
            new NotifyRealCloneGenerationJob($clone),
        ])->dispatch();

        return $this->success();
    }

    /**
     * Map the event type to the desired real clone status.
     */
    private function status(string $event): RealCloneStatus
    {
        return match ($event) {
            'avatar_video.success' => RealCloneStatus::COMPLETED,
            default => RealCloneStatus::FAILED,
        };
    }

    /**
     * Fetch the real clone with the given id.
     */
    private function fetchRealClone(string $id): ?RealClone
    {
        return RealClone::where('provider_id', $id)->where('provider', RealCloneProvider::HEYGEN->value)->first();
    }

    /**
     * Check if the real clone generation was completed.
     */
    private function isCompleted(RealCloneStatus $status): bool
    {
        return RealCloneStatus::COMPLETED === $status;
    }

    /**
     * Check if the real clone generation had failed.
     */
    private function hasFailed(RealCloneStatus $status): bool
    {
        return !$this->isCompleted($status);
    }

    /**
     * Whether the webhook is referent to the welcome real clone.
     */
    private function isWelcomeVideo(array $metadata): bool
    {
        return Arr::has($metadata, 'user_id', false);
    }

    /**
     * Marks the real clone as failed and refunds the user.
     */
    private function markAsFailed(RealClone $clone): void
    {
        Log::error('D-ID has failed to generate real clone {id}', [
            'id' => $clone->id,
        ]);

        DB::transaction(function () use ($clone) {
            (new CreditService($clone->user))->refund($clone);

            return tap($clone)->update([
                'status' => RealCloneStatus::FAILED,
                'synced_at' => null,
                'url' => null,
            ]);
        });
    }

    /**
     * Handle successful calls on the controller.
     */
    private function success(): Response
    {
        return new Response('Webhook Handled', 200);
    }
}
