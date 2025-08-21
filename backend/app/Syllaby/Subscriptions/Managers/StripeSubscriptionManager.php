<?php

namespace App\Syllaby\Subscriptions\Managers;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Laravel\Cashier\Cashier;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Syllaby\Subscriptions\Contracts\BillableContract;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;

class StripeSubscriptionManager implements BillableContract
{
    public function __construct(protected User $user) {}

    /**
     * Get a subscription instance by type.
     */
    public function subscription(string $type = 'default'): ?SubscriptionContract
    {
        return $this->subscriptions()->where('type', $type)->first();
    }

    /**
     * Get all subscriptions for the billable model.
     */
    public function subscriptions(): HasMany
    {
        return $this->user->hasMany(Cashier::$subscriptionModel, $this->user->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Determine if the model has a given subscription.
     */
    public function subscribed(string $type = 'default', mixed $plan = null): bool
    {
        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return ! $plan || $subscription->hasPrice($plan);
    }

    /**
     * Determine if the model is on trial.
     * Based on Laravel Cashier's ManagesSubscriptions trait logic.
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
            return $subscription->trial_ends_at;
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
        return ! is_null($this->subscriptions()->get()->first(function ($subscription) use ($price) {
            return $subscription->valid() && $subscription->hasPrice($price);
        }));
    }

    /**
     * Get the tax rates to apply to the subscription.
     */
    public function taxRates(): array
    {
        return [];
    }

    /**
     * Get the tax rates to apply to individual subscription items.
     */
    public function priceTaxRates(): array
    {
        return [];
    }
}
