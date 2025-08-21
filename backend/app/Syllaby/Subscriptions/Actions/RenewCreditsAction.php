<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;

class RenewCreditsAction
{
    /**
     * Sets the new credits.
     */
    public function handle(User $user): void
    {
        (new CreditService($user))->set(
            amount: max(0, $user->monthly_credit_amount),
            type: CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID,
            label: 'Subscription Renewed'
        );
    }
}
