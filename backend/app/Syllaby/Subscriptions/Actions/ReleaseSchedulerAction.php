<?php

namespace App\Syllaby\Subscriptions\Actions;

use Exception;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;

class ReleaseSchedulerAction
{
    /**
     * Release a scheduled plan change for a user's subscription.
     */
    public function handle(SubscriptionContract $subscription): bool
    {
        if (blank($subscription->scheduler_id)) {
            return true;
        }

        try {
            Cashier::stripe()->subscriptionSchedules->release(
                $subscription->scheduler_id
            );

            return $subscription->update(['scheduler_id' => null]);
        } catch (Exception $exception) {
            Log::error('Failed to cancel subscription schedule: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
