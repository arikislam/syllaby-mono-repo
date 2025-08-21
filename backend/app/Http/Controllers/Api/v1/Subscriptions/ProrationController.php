<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\PreviewProrationRequest;
use App\Syllaby\Subscriptions\Actions\PreviewProrationAction;

class ProrationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Show the proration for the subscription.
     */
    public function show(PreviewProrationRequest $request, PreviewProrationAction $proration): JsonResponse
    {
        $user = $request->user();

        if (! $proration = $proration->handle($user, $request->validated())) {
            return $this->errorInternalError('Failed to preview proration');
        }

        return $this->respondWithArray($proration);
    }
}
