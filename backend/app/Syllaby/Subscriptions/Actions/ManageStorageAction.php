<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Exceptions\SubscriptionUpdateFailure;
use App\Syllaby\Subscriptions\Notifications\SubscriptionStorageUpdated;

class ManageStorageAction
{
    /**
     * Handle the action.
     *
     * @throws SubscriptionUpdateFailure|IncompletePayment
     */
    public function handle(User $user, int $quantity): Subscription
    {
        $plan = $user->plan;
        $subscription = $user->subscription();
        $subscription->setRelation('owner', $user);

        $price = $this->storagePriceFor($plan->type);

        $this->updateStorage($subscription, $price, $quantity);

        $base = $plan->details('features.storage');
        Feature::for($user)->activate('max_storage', (string) ($base + $this->gigabytesToBytes($quantity)));

        $user->notify(new SubscriptionStorageUpdated($subscription, $quantity, $price));

        return $subscription;
    }

    /**
     * Convert bytes to gigabytes.
     */
    private function gigabytesToBytes(int $bytes): int
    {
        return $bytes * 1024 * 1024 * 1024;
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

    /**
     * Update the subscription quantity and invoice if needed.
     */
    private function updateStorage(Subscription $subscription, string $price, int $quantity): Subscription
    {
        if (! $subscription->hasPrice($price)) {
            return $subscription->addPriceAndInvoice($price, $quantity);
        }

        $subscription->updateQuantity($quantity, $price)->invoice([
            'description' => 'Storage increase',
        ]);

        return $subscription;
    }
}
