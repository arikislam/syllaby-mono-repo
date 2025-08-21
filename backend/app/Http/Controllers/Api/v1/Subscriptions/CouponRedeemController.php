<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\RedeemCouponRequest;
use App\Syllaby\Subscriptions\Actions\RedeemCouponAction;

class CouponRedeemController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(RedeemCouponRequest $request, RedeemCouponAction $redeem): JsonResponse
    {
        $user = $this->user();

        if (!$redeem->handle($user, $request->input('code'))) {
            return $this->errorInternalError('Whoops! It was not possible to redeem the coupon.');
        }

        return $this->respondWithMessage('Coupon successfully redeem.');
    }
}
