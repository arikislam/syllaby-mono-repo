<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Syllaby\RealClones\Jobs\CreateRealCloneMediaJob;
use App\Syllaby\RealClones\Jobs\NotifyRealCloneGenerationJob;

/**
 * {
 * "status": "200",
 * "video_url": "<video-url>",
 * "video_id":"<uuid>",
 * "model_id":"<uuid>",
 * }
 */
class FastVideoWebhookController extends Controller
{
    /**
     * Handles the user real clone creation from the video editor.
     */
    public function handle(Request $request): Response
    {
        Log::info('avatar:', $request->all());

        $status = $this->status($request->input('status'));

        if (!$clone = $this->fetchRealClone($id = $request->input('video_id'))) {
            Log::error('Real Clone video {id} not found on FastVideo', ['id' => $id]);

            return $this->success();
        }

        if ($this->hasFailed($status)) {
            $this->markAsFailed($clone);

            return $this->success();
        }

        $clone = tap($clone)->update([
            'status' => RealCloneStatus::SYNCING,
            'url' => $request->input('video_url'),
        ]);

        Bus::chain([
            new CreateRealCloneMediaJob($clone),
            new NotifyRealCloneGenerationJob($clone),
        ])->dispatch();

        return $this->success();
    }

    /**
     * Fetch the video with the given id.
     */
    private function fetchRealClone(string $id): ?RealClone
    {
        return RealClone::where('provider_id', $id)->where('provider', RealCloneProvider::FASTVIDEO->value)->first();
    }

    /**
     * Check if the video generation was completed.
     */
    private function isCompleted(RealCloneStatus $status): bool
    {
        return RealCloneStatus::COMPLETED === $status;
    }

    /**
     * Check if the video generation had failed.
     */
    private function hasFailed(RealCloneStatus $status): bool
    {
        return !$this->isCompleted($status);
    }

    /**
     * Maps the webhook status to a known one.
     */
    private function status(int $status): RealCloneStatus
    {
        return ($status !== 200) ? RealCloneStatus::FAILED : RealCloneStatus::COMPLETED;
    }

    /**
     * Marks the real clone as failed and refunds the user.
     */
    private function markAsFailed(RealClone $clone): void
    {
        Log::error('FastVideo has failed to generate real clone {id}', [
            'id' => $clone->id,
        ]);

        DB::transaction(function () use ($clone) {
            (new CreditService($clone->user))->refund($clone);

            return tap($clone)->update([
                'url' => null,
                'synced_at' => null,
                'status' => RealCloneStatus::FAILED,
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
