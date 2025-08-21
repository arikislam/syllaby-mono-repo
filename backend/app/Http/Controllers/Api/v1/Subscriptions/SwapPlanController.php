<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\SwapPlanRequest;
use App\Syllaby\Subscriptions\Actions\SwapPlanAction;

class SwapPlanController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Allows user to swap plans and prorate credits.
     */
    public function update(SwapPlanRequest $request, SwapPlanAction $swap): JsonResponse
    {
        $user = $this->user();

        if (! $swap->handle($user, $request->validated())) {
            return $this->errorInternalError('Whoops! There was an error while swapping plans.');
        }

        return $this->respondWithMessage('Success');
    }
}
