<?php

namespace App\Syllaby\Subscriptions\Actions;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\JVZooSubscription;
use App\Syllaby\Subscriptions\Enums\JVZooTransactionType;

class JVZooCancellationAction
{
    /**
     * Handle JVZoo subscription cancellation.
     */
    public function handle(User $user, JVZooTransaction $transaction, array $payload): void
    {
        if (! $subscription = $user->subscription()) {
            return;
        }

        DB::transaction(function () use ($subscription, $transaction) {
            $this->cancel($subscription, $transaction);
            $this->identify($transaction, $subscription);
        });
    }

    /**
     * Cancel the subscription with provided fields.
     */
    private function cancel(JVZooSubscription $subscription, JVZooTransaction $transaction): void
    {
        $fields = [
            'status' => JVZooSubscription::STATUS_CANCELED,
            'ends_at' => $this->calculateGracePeriodEnd($subscription),
        ];

        if ($this->shouldCancelImmediately($transaction)) {
            $fields['ends_at'] = now();
        }

        $subscription->update($fields);
    }

    /**
     * Calculate the end of the grace period.
     */
    private function calculateGracePeriodEnd(JVZooSubscription $subscription): Carbon
    {
        if ($subscription->started_at->isFuture()) {
            throw new InvalidArgumentException('Start date cannot be in the future');
        }

        if ($subscription->onTrial()) {
            return $subscription->trial_ends_at;
        }

        if ($subscription->ends_at) {
            return $subscription->ends_at;
        }

        return $this->calculatePeriodEnd(
            $subscription->started_at,
            $subscription->jvzooPlan->interval
        );
    }

    /**
     * Calculate the end of the current period.
     */
    private function calculatePeriodEnd(Carbon $startDate, string $interval): Carbon
    {
        $now = now();
        $anchorDay = $startDate->day;
        
        if ($interval === 'month') {
            // Get the current month's last day
            $lastDayOfCurrentMonth = $now->copy()->endOfMonth()->day;
            
            // Normalize the billing day for the current month (like Stripe does)
            $billingDay = min($anchorDay, $lastDayOfCurrentMonth);
            
            // Create the billing date for this month
            $billingDate = $now->copy()->day($billingDay);
            
            // If we've already passed the billing date this month, move to next month
            if ($billingDate->lte($now)) {
                $billingDate->addMonth();
                // Normalize again for the next month
                $lastDayOfNextMonth = $billingDate->copy()->endOfMonth()->day;
                $billingDate->day(min($anchorDay, $lastDayOfNextMonth));
            }
            
            return $billingDate->endOfDay();
        }
        
        // For yearly subscriptions, use the original month and day
        $billingDate = $now->copy()
            ->month($startDate->month)
            ->day($anchorDay);
        
        // If we've already passed this year's billing date, move to next year
        if ($billingDate->lte($now)) {
            $billingDate->addYear();
        }
        
        return $billingDate->endOfDay();
    }

    /**
     * Link the cancellation purchase to the subscription.
     */
    private function identify(JVZooTransaction $transaction, JVZooSubscription $subscription): void
    {
        $transaction->update([
            'user_id' => $subscription->user_id,
            'jvzoo_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Check if subscription should be cancelled immediately based on transaction type.
     */
    private function shouldCancelImmediately(JVZooTransaction $transaction): bool
    {
        return in_array($transaction->transaction_type, [
            JVZooTransactionType::RFND,
            JVZooTransactionType::CGBK,
        ]);
    }
}
