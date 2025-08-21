<?php

namespace App\Syllaby\Subscriptions\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface BillableContract
{
    /**
     * Get a subscription instance by type.
     */
    public function subscription(string $type = 'default'): ?SubscriptionContract;

    /**
     * Get all subscriptions for the billable model.
     */
    public function subscriptions(): HasMany;

    /**
     * Determine if the model has a given subscription.
     */
    public function subscribed(string $type = 'default', mixed $plan = null): bool;

    /**
     * Determine if the model is on trial.
     */
    public function onTrial(string $type = 'default', ?string $price = null): bool;

    /**
     * Determine if the model's trial has expired.
     */
    public function hasExpiredTrial(string $type = 'default', ?string $price = null): bool;

    /**
     * Determine if the model is on a "generic" trial at the model level.
     */
    public function onGenericTrial(): bool;

    /**
     * Determine if the model's "generic" trial has expired.
     */
    public function hasExpiredGenericTrial(): bool;

    /**
     * Get the ending date of the trial.
     */
    public function trialEndsAt(string $type = 'default'): ?Carbon;

    /**
     * Determine if the customer's subscription has an incomplete payment.
     */
    public function hasIncompletePayment(string $type = 'default'): bool;

    /**
     * Determine if the model is actively subscribed to one of the given prices.
     */
    public function subscribedToPrice($prices, string $type = 'default'): bool;

    /**
     * Determine if the customer has a valid subscription on the given price.
     */
    public function onPrice(string $price): bool;

    /**
     * Get the tax rates to apply to the subscription.
     */
    public function taxRates(): array;

    /**
     * Get the tax rates to apply to individual subscription items.
     */
    public function priceTaxRates(): array;
}
