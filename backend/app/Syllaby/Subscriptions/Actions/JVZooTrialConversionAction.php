<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\JVZooSubscription;

class JVZooTrialConversionAction
{
    /**
     * Convert trial subscription to active (when user makes payment).
     */
    public function handle(User $user, JVZooTransaction $transaction, array $payload): void
    {
        /** @var JVZooSubscription $subscription */
        $subscription = $user->subscription();

        if (! $subscription->onTrial()) {
            return;
        }

        DB::transaction(function () use ($transaction, $user, $subscription) {
            $subscription->update([
                'status' => JVZooSubscription::STATUS_ACTIVE,
            ]);

            $transaction->update([
                'user_id' => $user->id,
                'jvzoo_subscription_id' => $subscription->id,
            ]);

            $this->awardCredits($user, $subscription);
        });
    }

    /**
     * Cancel subscription when trial period expires without payment.
     */
    public function cancelExpiredTrial(JVZooSubscription $subscription): void
    {
        if (! $subscription->onTrial()) {
            return;
        }

        $subscription->update([
            'ends_at' => now(),
            'status' => JVZooSubscription::STATUS_CANCELLED,
        ]);
    }

    /**
     * Award subscription credits after trial conversion.
     */
    private function awardCredits(User $user, JVZooSubscription $subscription): void
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
