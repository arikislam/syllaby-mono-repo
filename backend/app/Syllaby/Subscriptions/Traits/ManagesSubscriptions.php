<?php

namespace App\Syllaby\Subscriptions\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Syllaby\Subscriptions\Contracts\BillableContract;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;
use App\Syllaby\Subscriptions\Managers\JVZooSubscriptionManager;
use App\Syllaby\Subscriptions\Managers\StripeSubscriptionManager;
use App\Syllaby\Subscriptions\Managers\GooglePlaySubscriptionManager;

trait ManagesSubscriptions
{
    /**
     * Get a subscription by name.
     */
    public function subscription(string $name = 'default'): ?SubscriptionContract
    {
        return $this->billable()->subscription($name);
    }

    /**
     * Get all subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->billable()->subscriptions();
    }

    /**
     * Check if the user is subscribed to any plan.
     */
    public function subscribed(string $subscription = 'default', mixed $plan = null): bool
    {
        return $this->billable()->subscribed($subscription, $plan);
    }

    /**
     * Determine if the model is on trial.
     */
    public function onTrial(string $type = 'default', ?string $price = null): bool
    {
        return $this->billable()->onTrial($type, $price);
    }

    /**
     * Determine if the model's trial has ended.
     */
    public function hasExpiredTrial(string $type = 'default', ?string $price = null): bool
    {
        return $this->billable()->hasExpiredTrial($type, $price);
    }

    /**
     * Determine if the model is on a "generic" trial at the model level.
     */
    public function onGenericTrial(): bool
    {
        return $this->billable()->onGenericTrial();
    }

    /**
     * Determine if the model's "generic" trial at the model level has expired.
     */
    public function hasExpiredGenericTrial(): bool
    {
        return $this->billable()->hasExpiredGenericTrial();
    }

    /**
     * Get the ending date of the trial.
     */
    public function trialEndsAt(string $type = 'default'): ?Carbon
    {
        return $this->billable()->trialEndsAt($type);
    }

    /**
     * Determine if the customer's subscription has an incomplete payment.
     */
    public function hasIncompletePayment(string $type = 'default'): bool
    {
        return $this->billable()->hasIncompletePayment($type);
    }

    /**
     * Determine if the model is actively subscribed to one of the given prices.
     */
    public function subscribedToPrice($prices, string $type = 'default'): bool
    {
        return $this->billable()->subscribedToPrice($prices, $type);
    }

    /**
     * Determine if the customer has a valid subscription on the given price.
     */
    public function onPrice(string $price): bool
    {
        return $this->billable()->onPrice($price);
    }

    /**
     * Get the tax rates to apply to the subscription.
     */
    public function taxRates(): array
    {
        return $this->billable()->taxRates();
    }

    /**
     * Get the tax rates to apply to individual subscription items.
     */
    public function priceTaxRates(): array
    {
        return $this->billable()->priceTaxRates();
    }

    /**
     * Get the subscription manager based on the user's provider.
     */
    protected function billable(): BillableContract
    {
        return match ($this->subscription_provider) {
            SubscriptionProvider::STRIPE => new StripeSubscriptionManager($this),
            SubscriptionProvider::GOOGLE_PLAY => new GooglePlaySubscriptionManager($this),
            SubscriptionProvider::JVZOO => new JVZooSubscriptionManager($this),
            default => new StripeSubscriptionManager($this),
        };
    }
}
