<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionRenewed;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionPurchased;

class GooglePlaySetSubscriptionCreditsListener
{
    /**
     * Handle the subscription purchased event.
     */
    public function handle(GooglePlaySubscriptionPurchased|GooglePlaySubscriptionRenewed $event): void
    {
        $user = $event->user;
        $rtdn = $event->rtdn;
        $plan = $rtdn->plan;

        if (! $plan) {
            Log::warning('Google Play subscription event without plan', [
                'rtdn_id' => $rtdn->id,
                'user_id' => $user->id,
                'event_type' => get_class($event),
            ]);

            return;
        }

        // Get credits from plan meta based on trial status
        // Check if user is on trial to give appropriate credits
        if ($user->onTrial()) {
            $credits = $plan->meta['trial_credits'] ?? 0;
            $creditType = CreditEventEnum::SUBSCRIBE_TO_TRIAL;
        } else {
            $credits = $plan->meta['full_credits'] ?? 0;
            $creditType = CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID;
        }

        if ($credits <= 0) {
            Log::info('Google Play subscription has no credits to add', [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'event_type' => get_class($event),
            ]);

            return;
        }

        // Determine the label based on event type
        $isRenewal = $event instanceof GooglePlaySubscriptionRenewed;
        $label = $isRenewal ? 'Google Play Subscription Renewed' : 'Google Play Subscription Purchased';

        // Add credits to user using CreditService in transaction to prevent double-crediting
        DB::transaction(function () use ($user, $credits, $creditType, $label, $rtdn, $plan, $event, $isRenewal) {
            // Check if credits were added within the last 4 minutes to prevent duplicates
            // Check for both trial and subscription credits to prevent duplicates
            $recentCredit = CreditHistory::where('user_id', $user->id)
                ->whereIn('description', [
                    CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value,
                ])
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($recentCredit) {
                Log::info('Google Play subscription credits recently added, skipping', [
                    'user_id' => $user->id,
                    'rtdn_id' => $rtdn->id,
                    'recent_credit_id' => $recentCredit->id,
                    'recent_credit_time' => $recentCredit->created_at,
                    'recent_credit_type' => $recentCredit->description,
                ]);

                return;
            }

            // Add credits to user using CreditService
            $creditService = new CreditService($user);
            $creditService->set(
                amount: $credits,
                type: $creditType,
                label: $label
            );

            Log::info('Google Play subscription credits added', [
                'user_id' => $user->id,
                'credits' => $credits,
                'credit_type' => $creditType->value,
                'is_trial' => $user->onTrial(),
                'plan_id' => $plan->id,
                'purchase_token' => $rtdn->purchase_token,
                'event_type' => get_class($event),
                'is_renewal' => $isRenewal,
            ]);
        });
    }
}
