<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\JVZooSubscription;

class JVZooRenewalAction
{
    /**
     * Handle recurring payment renewal (BILL transaction for active subscriptions).
     */
    public function handleRenewal(User $user, JVZooTransaction $transaction, array $payload): void
    {
        /** @var JVZooSubscription $subscription */
        if (! $subscription = $user->subscription()) {
            return;
        }

        DB::transaction(function () use ($transaction, $user, $subscription) {
            $transaction->update([
                'user_id' => $user->id,
                'jvzoo_subscription_id' => $subscription->id,
            ]);

            if ($this->isPaymentRecovery($subscription)) {
                $subscription->update(['status' => JVZooSubscription::STATUS_ACTIVE]);
            }

            $this->credits($user, $subscription);
        });
    }

    /**
     * Check if this is a payment recovery scenario (subscription is past due).
     */
    private function isPaymentRecovery(JVZooSubscription $subscription): bool
    {
        return $subscription->status === JVZooSubscription::STATUS_EXPIRED;
    }

    /**
     * Award credits for subscription renewal.
     */
    private function credits(User $user, JVZooSubscription $subscription): void
    {
        if (! $plan = $subscription->jvzooPlan) {
            return;
        }

        $credits = Arr::get($plan->metadata, 'full_credits', 500);
        
        if ($plan->interval === 'yearly') {
            $credits = (int) ($credits / 12);
        }

        (new CreditService($user))->set($credits, CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID, $credits);
    }
}
