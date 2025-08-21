<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\JVZooPlan;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Auth\Actions\RegistrationAction;
use App\Syllaby\Subscriptions\JVZooSubscription;
use App\Syllaby\Subscriptions\JVZooSubscriptionItem;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;
use App\Syllaby\Subscriptions\Notifications\JVZooOnboardingNotification;

class JVZooOnboardingAction
{
    public function __construct(private RegistrationAction $register) {}

    /**
     * Handle complete JVZoo user onboarding process.
     */
    public function handle(JVZooTransaction $transaction, array $payload): User
    {
        return DB::transaction(function () use ($payload, $transaction) {
            $user = $this->createUser($payload);
            $plan = $this->findOrCreatePlan($payload);
            $subscription = $this->subscribe($user, $plan, $transaction);

            $this->identify($transaction, $user, $subscription);
            $user->notify(new JVZooOnboardingNotification($transaction, $subscription));

            return $user;
        });
    }

    /**
     * Create user account using RegistrationAction with JVZoo-specific settings.
     */
    private function createUser(array $payload): User
    {
        return $this->register->handle([
            'mailing_list' => false,
            'email_verified_at' => now(),
            'password' => Str::random(32),
            'name' => Arr::get($payload, 'ccustname'),
            'email' => Arr::get($payload, 'ccustemail'),
            'source' => SubscriptionProvider::SOURCE_JVZOO,
        ]);
    }

    /**
     * Find existing JVZoo plan.
     */
    private function findOrCreatePlan(array $payload): JVZooPlan
    {
        $product = Arr::get($payload, 'cproditem');

        // Plan should already exist from ProcessJVZooTransactionAction
        $plan = JVZooPlan::where('jvzoo_id', $product)->first();
        
        if (!$plan) {
            throw new \Exception("JVZoo plan not found for product ID: {$product}");
        }

        return $plan;
    }

    /**
     * Create JVZoo subscription for the user.
     */
    private function subscribe(User $user, JVZooPlan $plan, JVZooTransaction $transaction): JVZooSubscription
    {
        $trial = $transaction->amount === 0;

        $subscription = JVZooSubscription::create([
            'ends_at' => null,
            'user_id' => $user->id,
            'jvzoo_plan_id' => $plan->id,
            'receipt' => $transaction->receipt,
            'started_at' => now(),
            'status' => $trial ? JVZooSubscription::STATUS_TRIAL : JVZooSubscription::STATUS_ACTIVE,
            'trial_ends_at' => $trial ? now()->addDays(config('services.jvzoo.trial_days', 7)) : null,
        ]);

        JVZooSubscriptionItem::create([
            'quantity' => 1,
            'jvzoo_plan_id' => $plan->id,
            'jvzoo_subscription_id' => $subscription->id,
        ]);

        $creditAmount = $trial 
            ? Arr::get($plan->metadata, 'trial_credits', 50)
            : Arr::get($plan->metadata, 'full_credits', 500);
        
        if ($plan->interval === 'yearly' && !$trial) {
            $creditAmount = (int) ($creditAmount / 12);
        }

        $user->update([
            'plan_id' => $plan->plan_id,
        ]);

        $creditService = new CreditService($user);
        $creditService->set(
            $creditAmount,
            $trial ? CreditEventEnum::SUBSCRIBE_TO_TRIAL : CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID,
            $creditAmount
        );

        return $subscription;
    }

    /**
     * Link purchase to user and subscription, and set onboarding fields.
     */
    private function identify(JVZooTransaction $transaction, User $user, JVZooSubscription $subscription): void
    {
        $transaction->update([
            'user_id' => $user->id,
            'jvzoo_subscription_id' => $subscription->id,
            'onboarding_token' => Str::uuid()->toString(),
            'onboarding_expires_at' => now()->addHours(48),
        ]);
    }

}
