<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Laravel\Cashier\Exceptions\SubscriptionUpdateFailure;

class CancelStorageAction
{
    /**
     * Handle the action.
     *
     * @throws SubscriptionUpdateFailure
     */
    public function handle(User $user): void
    {
        $plan = $user->plan;

        $subscription = $user->subscription();
        $subscription->setRelation('owner', $user);

        $price = $this->storagePriceFor($plan->type);

        if (! $subscription->hasPrice($price)) {
            return;
        }

        $subscription->removePrice($price);
        Feature::for($user)->activate('max_storage', $plan->details('features.storage'));
    }

    /**
     * Get the storage price for the given type.
     */
    private function storagePriceFor(string $type): string
    {
        if ($type === 'month') {
            return config('services.stripe.add_ons.storage.monthly');
        }

        return config('services.stripe.add_ons.storage.yearly');
    }
}
