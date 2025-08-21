<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Syllaby\RealClones\Jobs\CreateRealCloneMediaJob;
use App\Syllaby\RealClones\Jobs\NotifyRealCloneGenerationJob;

class DiDWebhookController extends Controller
{
    /**
     * Handle income event requests from D-ID webhook.
     */
    public function handle(Request $request): Response
    {
        $status = $this->status($request->input('status'));

        if (! $clone = $this->fetchRealClone($id = $request->input('id'))) {
            Log::error('Real Clone video {id} not found on D-ID', ['id' => $id]);

            return $this->success();
        }

        if ($this->hasFailed($status)) {
            $this->markAsFailed($clone);

            return $this->success();
        }

        $clone = tap($clone)->update([
            'status' => RealCloneStatus::SYNCING,
            'url' => $request->input('result_url'),
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
        return RealClone::where('provider_id', $id)->where('provider', RealCloneProvider::D_ID->value)->first();
    }

    /**
     * Check if the video generation was completed.
     */
    private function isCompleted(RealCloneStatus $status): bool
    {
        return $status === RealCloneStatus::COMPLETED;
    }

    /**
     * Check if the video generation had failed.
     */
    private function hasFailed(RealCloneStatus $status): bool
    {
        return ! $this->isCompleted($status);
    }

    /**
     * Maps the webhook status to a known one.
     */
    private function status(string $status): RealCloneStatus
    {
        return match ($status) {
            'error', 'rejected' => RealCloneStatus::FAILED,
            'started', 'created' => RealCloneStatus::GENERATING,
            default => RealCloneStatus::COMPLETED,
        };
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
