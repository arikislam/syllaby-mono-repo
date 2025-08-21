<?php

namespace App\Syllaby\Subscriptions\Managers;

use Exception;
use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\JVZooSubscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Syllaby\Subscriptions\Contracts\BillableContract;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;

class JVZooSubscriptionManager implements BillableContract
{
    public function __construct(protected User $user) {}

    /**
     * Get a subscription instance by type.
     */
    public function subscription(string $type = 'default'): ?SubscriptionContract
    {
        return $this->subscriptions()->first();
    }

    /**
     * Get all subscriptions for the billable model.
     */
    public function subscriptions(): HasMany
    {
        return $this->user->hasMany(JVZooSubscription::class, $this->user->getForeignKey())
            ->latest('created_at');
    }

    /**
     * Determine if the model has a given subscription.
     */
    public function subscribed(string $type = 'default', mixed $plan = null): bool
    {
        $subscription = $this->subscription();

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return ! $plan || $subscription->hasPrice($plan);
    }

    /**
     * Determine if the model is on trial.
     */
    public function onTrial(string $type = 'default', ?string $price = null): bool
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->onTrial()) {
            return false;
        }

        return ! $price || $subscription->hasPrice($price);
    }

    /**
     * Determine if the model's trial has expired.
     */
    public function hasExpiredTrial(string $type = 'default', ?string $price = null): bool
    {
        if (func_num_args() === 0 && $this->hasExpiredGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->hasExpiredTrial()) {
            return false;
        }

        return ! $price || $subscription->hasPrice($price);
    }

    /**
     * Determine if the model is on a "generic" trial at the model level.
     */
    public function onGenericTrial(): bool
    {
        return $this->user->trial_ends_at && $this->user->trial_ends_at->isFuture();
    }

    /**
     * Determine if the model's "generic" trial has expired.
     */
    public function hasExpiredGenericTrial(): bool
    {
        return $this->user->trial_ends_at && $this->user->trial_ends_at->isPast();
    }

    /**
     * Get the ending date of the trial.
     */
    public function trialEndsAt(string $type = 'default'): ?Carbon
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return $this->user->trial_ends_at;
        }

        if ($subscription = $this->subscription($type)) {
            return $subscription->trialEndsAt();
        }

        return $this->user->trial_ends_at;
    }

    /**
     * Determine if the customer's subscription has an incomplete payment.
     */
    public function hasIncompletePayment(string $type = 'default'): bool
    {
        if ($subscription = $this->subscription($type)) {
            return $subscription->hasIncompletePayment();
        }

        return false;
    }

    /**
     * Determine if the model is actively subscribed to one of the given prices.
     */
    public function subscribedToPrice($prices, string $type = 'default'): bool
    {
        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        foreach ((array) $prices as $price) {
            if ($subscription->hasPrice($price)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the customer has a valid subscription on the given price.
     */
    public function onPrice(string $price): bool
    {
        return ! is_null($this->subscriptions()->get()->first(
            fn (SubscriptionContract $subscription) => $subscription->valid() && $subscription->hasPrice($price)
        ));
    }

    /**
     * Get the tax rates to apply to the subscription.
     */
    public function taxRates(): array
    {
        throw new Exception('JVZoo does not support tax rates');
    }

    /**
     * Get the tax rates to apply to individual subscription items.
     */
    public function priceTaxRates(): array
    {
        throw new Exception('JVZoo does not support tax rates');
    }
}
