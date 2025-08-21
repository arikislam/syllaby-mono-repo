<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Subscriptions\Actions\ReleaseSchedulerAction;

class CancelDowngradeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * @throws Exception
     */
    public function update(ReleaseSchedulerAction $scheduler): JsonResponse
    {
        $user = $this->user();

        if (! $scheduler->handle($user->subscription())) {
            return $this->errorInternalError('Failed to cancel downgrade');
        }

        return $this->respondWithMessage('Downgrade cancelled');
    }
}
