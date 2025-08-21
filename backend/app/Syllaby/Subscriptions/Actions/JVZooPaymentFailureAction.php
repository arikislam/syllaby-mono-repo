<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\JVZooSubscription;

class JVZooPaymentFailureAction
{
    /**
     * Handle JVZoo payment failure (INSF - Insufficient Funds).
     */
    public function handle(User $user, JVZooTransaction $transaction, array $payload): void
    {
        if (! $subscription = $user->subscription()) {
            return;
        }

        DB::transaction(function () use ($subscription, $transaction) {
            $this->markAsPastDue($subscription);
            $this->identify($transaction, $subscription);
        });
    }

    /**
     * Mark the subscription as past due.
     */
    private function markAsPastDue(JVZooSubscription $subscription): void
    {
        $subscription->update([
            'status' => JVZooSubscription::STATUS_EXPIRED,
        ]);

    }

    /**
     * Link the failed payment transaction to the subscription.
     */
    private function identify(JVZooTransaction $transaction, JVZooSubscription $subscription): void
    {
        $transaction->update([
            'user_id' => $subscription->user_id,
            'jvzoo_subscription_id' => $subscription->id,
        ]);
    }
}
