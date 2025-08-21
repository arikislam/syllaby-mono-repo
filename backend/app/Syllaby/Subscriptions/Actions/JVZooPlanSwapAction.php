<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\JVZooPlan;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\JVZooSubscription;

class JVZooPlanSwapAction
{
    /**
     * Handle JVZoo plan swap for existing user.
     */
    public function handle(User $user, JVZooTransaction $transaction, array $payload): JVZooSubscription
    {
        return DB::transaction(function () use ($user, $transaction, $payload) {
            $newPlan = $this->findOrCreatePlan($payload);

            $currentSubscription = $user->subscription();
            $subscription = $this->createOrUpdateSubscription($user, $newPlan, $transaction, $currentSubscription);

            $this->linkPurchaseToUser($transaction, $user, $subscription);

            $this->swap($user, $newPlan);

            $this->awardCredits($user, $newPlan);

            return $subscription;
        });
    }

    /**
     * Find existing JVZoo plan.
     */
    private function findOrCreatePlan(array $payload): JVZooPlan
    {
        $productId = $payload['cproditem'];

        return JVZooPlan::where('jvzoo_id', $productId)->firstOrFail();
    }

    /**
     * Create new subscription for plan swap.
     */
    private function createOrUpdateSubscription(User $user, JVZooPlan $newPlan, JVZooTransaction $transaction): JVZooSubscription
    {
        $subscription = $user->subscriptions()->whereIn('status', [
            JVZooSubscription::STATUS_ACTIVE,
            JVZooSubscription::STATUS_TRIAL,
            JVZooSubscription::STATUS_EXPIRED,
        ])->first();

        if ($subscription) {
            return $subscription;
        }

        // Only create a new subscription if no active subscription exists
        $subscription = JVZooSubscription::create([
            'user_id' => $user->id,
            'jvzoo_plan_id' => $newPlan->id,
            'receipt' => $transaction->receipt,
            'status' => JVZooSubscription::STATUS_ACTIVE,
            'started_at' => now(),
            'ends_at' => $this->calculateSubscriptionEndDate($newPlan, $transaction),
        ]);

        // Create subscription item
        $subscription->items()->create([
            'jvzoo_plan_id' => $newPlan->id,
            'quantity' => 1,
        ]);

        return $subscription;
    }

    /**
     * Link purchase to user and subscription.
     */
    private function linkPurchaseToUser(JVZooTransaction $transaction, User $user, JVZooSubscription $subscription): void
    {
        $transaction->update([
            'user_id' => $user->id,
            'jvzoo_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Update user's plan information.
     */
    private function swap(User $user, JVZooPlan $newPlan): void
    {
        $user->update([
            'plan_id' => $newPlan->plan_id,
        ]);
    }

    /**
     * Calculate subscription end date based on plan type.
     */
    private function calculateSubscriptionEndDate(JVZooPlan $plan, JVZooTransaction $transaction): ?\Carbon\Carbon
    {
        // JVZoo subscriptions don't have an end date until cancelled
        return null;
    }

    /**
     * Award credits for the new plan.
     */
    private function awardCredits(User $user, JVZooPlan $plan): void
    {
        $credits = Arr::get($plan->metadata, 'full_credits', 500);

        if ($plan->interval === 'yearly') {
            $credits = (int) ($credits / 12);
        }

        (new CreditService($user))->set($credits, CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID, $credits);
    }
}
