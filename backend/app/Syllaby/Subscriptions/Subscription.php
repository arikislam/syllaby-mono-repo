<?php

namespace App\Syllaby\Subscriptions;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Subscription as CashierSubscription;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;

class Subscription extends CashierSubscription implements SubscriptionContract
{
    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return parent::user();
    }

    /**
     * Get the model related to the subscription.
     */
    public function owner(): BelongsTo
    {
        return parent::owner();
    }

    /**
     * Get the subscription items related to the subscription.
     */
    public function items(): HasMany
    {
        return parent::items();
    }

    /**
     * Get the subscription item related to the subscription.
     */
    public function findItemOrFail($price): mixed
    {
        return parent::findItemOrFail($price);
    }

    /**
     * Get the subscription items related to the subscription.
     */
    public function hasMultiplePrices(): bool
    {
        return parent::hasMultiplePrices();
    }

    /**
     * Get the subscription items related to the subscription.
     */
    public function hasSinglePrice(): bool
    {
        return parent::hasSinglePrice();
    }

    /**
     * Check if the subscription is active.
     */
    public function active(): bool
    {
        return parent::active();
    }

    /**
     * Scope a query for active subscriptions.
     */
    public function scopeActive($query): void
    {
        parent::scopeActive($query);
    }

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool
    {
        return parent::onTrial();
    }

    /**
     * Scope a query for subscriptions on trial.
     */
    public function scopeOnTrial($query): void
    {
        parent::scopeOnTrial($query);
    }

    /**
     * Check if the subscription is canceled.
     */
    public function canceled(): bool
    {
        return parent::canceled();
    }

    /**
     * Scope a query for canceled subscriptions.
     */
    public function scopeCanceled($query): void
    {
        parent::scopeCanceled($query);
    }

    /**
     * Scope a query for not canceled subscriptions.
     */
    public function scopeNotCanceled($query): void
    {
        parent::scopeNotCanceled($query);
    }

    /**
     * Check if the subscription is expired.
     */
    public function expired(): bool
    {
        return $this->ended();
    }

    /**
     * Check if the subscription is on grace period.
     */
    public function onGracePeriod(): bool
    {
        return parent::onGracePeriod();
    }

    /**
     * Scope a query for subscriptions on grace period.
     */
    public function scopeOnGracePeriod($query): void
    {
        parent::scopeOnGracePeriod($query);
    }

    /**
     * Scope a query for not on grace period.
     */
    public function scopeNotOnGracePeriod($query): void
    {
        parent::scopeNotOnGracePeriod($query);
    }

    /**
     * Check if the subscription is recurring.
     */
    public function recurring(): bool
    {
        return parent::recurring();
    }

    /**
     * Scope a query for recurring subscriptions.
     */
    public function scopeRecurring($query): void
    {
        parent::scopeRecurring($query);
    }

    /**
     * Check if the subscription is incomplete.
     */
    public function incomplete(): bool
    {
        return parent::incomplete();
    }

    /**
     * Scope a query for incomplete subscriptions.
     */
    public function scopeIncomplete($query): void
    {
        parent::scopeIncomplete($query);
    }

    /**
     * Check if the subscription is past due.
     */
    public function pastDue(): bool
    {
        return parent::pastDue();
    }

    /**
     * Scope a query for past due subscriptions.
     */
    public function scopePastDue($query): void
    {
        parent::scopePastDue($query);
    }

    /**
     * Check if the subscription has ended and the grace period has expired.
     */
    public function ended(): bool
    {
        return parent::ended();
    }

    /**
     * Scope a query for ended subscriptions.
     */
    public function scopeEnded($query): void
    {
        parent::scopeEnded($query);
    }

    /**
     * Check if the subscription's trial has expired.
     */
    public function hasExpiredTrial(): bool
    {
        return parent::hasExpiredTrial();
    }

    /**
     * Scope a query for subscriptions with expired trial.
     */
    public function scopeExpiredTrial($query): void
    {
        parent::scopeExpiredTrial($query);
    }

    /**
     * Scope a query for subscriptions that are not on trial.
     */
    public function scopeNotOnTrial($query): void
    {
        parent::scopeNotOnTrial($query);
    }

    /**
     * Get the subscription's trial end date.
     */
    public function trialEndsAt(): ?Carbon
    {
        return $this->trial_ends_at;
    }

    /**
     * Get the subscription's end date.
     */
    public function endsAt(): ?Carbon
    {
        return $this->ends_at;
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(): SubscriptionContract
    {
        parent::cancel();

        return $this;
    }

    /**
     * Get the plan ID associated with this subscription.
     */
    public function planId(): mixed
    {
        return $this->price_id ?? $this->stripe_price;
    }

    /**
     * Check if the subscription has a specific plan.
     */
    public function hasPrice(mixed $plan): bool
    {
        return parent::hasPrice($plan);
    }

    /**
     * Check if the subscription is valid (active, on trial, or on grace period).
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Check if the subscription has incomplete payment.
     */
    public function hasIncompletePayment(): bool
    {
        return parent::hasIncompletePayment();
    }

    /**
     * Get the status attribute (accessor to normalize stripe_status as status).
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stripe_status,
        );
    }
}
