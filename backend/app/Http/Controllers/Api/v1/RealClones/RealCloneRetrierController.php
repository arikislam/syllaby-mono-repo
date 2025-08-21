<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use App\Http\Resources\RealCloneResource;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\RealClones\Jobs\CreateRealCloneMediaJob;
use App\Syllaby\RealClones\Jobs\NotifyRealCloneGenerationJob;

class RealCloneRetrierController extends Controller
{
    const int MAX_RETRIES = 1;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Retries to re-sync the given real clone.
     */
    public function store(RealClone $clone): JsonResponse
    {
        $this->authorize('update', $clone);

        if ($clone->status !== RealCloneStatus::SYNC_FAILED) {
            return $this->errorForbidden('The real clone cannot be re-synced.');
        }

        if ($clone->retries === self::MAX_RETRIES) {
            return $this->respondWithResource(RealCloneResource::make(
                $this->markAsFailed($clone)
            ));
        }

        $clone = tap($clone)->update([
            'retries' => ++$clone->retries,
            'status' => RealCloneStatus::SYNCING,
        ]);

        Bus::chain([
            new CreateRealCloneMediaJob($clone),
            new NotifyRealCloneGenerationJob($clone),
        ])->dispatch();

        return $this->respondWithResource(
            resource: RealCloneResource::make($clone),
            status: Response::HTTP_ACCEPTED,
            message: 'Real Clone queued for re-sync'
        );
    }

    /**
     * Marks the real clone as failed and refunds the user.
     */
    private function markAsFailed(RealClone $clone): RealClone
    {
        return DB::transaction(function () use ($clone) {
            (new CreditService($clone->user))->refund($clone);

            return tap($clone)->update([
                'url' => null,
                'synced_at' => null,
                'status' => RealCloneStatus::FAILED,
            ]);
        });
    }
}
