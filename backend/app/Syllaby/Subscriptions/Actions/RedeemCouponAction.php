<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\Coupon;
use App\Actions\Api\Subscriptions\Allows;

class RedeemCouponAction
{
    /**
     * Allows user to redeem the given coupon.
     *
     * @return Allows user to redeem the given coupon.
     */
    public function handle(User $user, string $code): bool
    {
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon->users()->where('user_id', $user->id)->exists()) {
            return false;
        }

        $user->coupons()->attach($coupon->id);

        $user->subscription()->load('owner')->updateStripeSubscription([
            'coupon' => $coupon->code,
        ]);

        return true;
    }
}
