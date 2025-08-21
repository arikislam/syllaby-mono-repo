<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Subscriptions\Actions\RedeemCouponAction;
use App\Http\Requests\Subscriptions\ResumeSubscriptionRequest;
use App\Syllaby\Subscriptions\Actions\ResumeSubscriptionAction;

class SubscriptionResumeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(ResumeSubscriptionRequest $request, ResumeSubscriptionAction $resume, RedeemCouponAction $redeem): JsonResponse
    {
        try {
            $resume->handle($this->user());

            if ($request->filled('code')) {
                $redeem->handle($this->user(), $request->input('code'));
            }
        } catch (Exception $error) {
            Log::error($error->getMessage());

            return $this->errorInternalError('Whoops! It was not possible to resume your subscription.');
        }

        return $this->respondWithMessage('Subscription resumed successfully.');
    }
}
