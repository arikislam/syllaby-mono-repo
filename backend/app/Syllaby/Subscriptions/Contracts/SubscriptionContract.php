<?php

namespace App\Syllaby\Subscriptions\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface SubscriptionContract
{
    /**
     * Get the user associated with the subscription.
     */
    public function user(): BelongsTo;

    /**
     * Get the owner associated with the subscription.
     */
    public function owner(): BelongsTo;

    /**
     * Get the subscription items.
     */
    public function items(): HasMany;

    /**
     * Find a subscription item by price or fail.
     */
    public function findItemOrFail($price): mixed;

    /**
     * Check if the subscription is active.
     */
    public function active(): bool;

    /**
     * Scope a query for active subscriptions.
     */
    public function scopeActive($query): void;

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool;

    /**
     * Scope a query for subscriptions on trial.
     */
    public function scopeOnTrial($query): void;

    /**
     * Check if the subscription is canceled.
     */
    public function canceled(): bool;

    /**
     * Scope a query for canceled subscriptions.
     */
    public function scopeCanceled($query): void;

    /**
     * Scope a query for subscriptions that are not canceled.
     */
    public function scopeNotCanceled($query): void;

    /**
     * Check if the subscription is on grace period.
     */
    public function onGracePeriod(): bool;

    /**
     * Scope a query for subscriptions on grace period.
     */
    public function scopeOnGracePeriod($query): void;

    /**
     * Scope a query for subscriptions not on grace period.
     */
    public function scopeNotOnGracePeriod($query): void;

    /**
     * Check if the subscription is recurring.
     */
    public function recurring(): bool;

    /**
     * Scope a query for recurring subscriptions.
     */
    public function scopeRecurring($query): void;

    /**
     * Check if the subscription is incomplete.
     */
    public function incomplete(): bool;

    /**
     * Scope a query for incomplete subscriptions.
     */
    public function scopeIncomplete($query): void;

    /**
     * Check if the subscription is past due.
     */
    public function pastDue(): bool;

    /**
     * Scope a query for past due subscriptions.
     */
    public function scopePastDue($query): void;

    /**
     * Check if the subscription has ended and the grace period has expired.
     */
    public function ended(): bool;

    /**
     * Scope a query for ended subscriptions.
     */
    public function scopeEnded($query): void;

    /**
     * Check if the subscription's trial has expired.
     */
    public function hasExpiredTrial(): bool;

    /**
     * Scope a query for subscriptions with expired trial.
     */
    public function scopeExpiredTrial($query): void;

    /**
     * Scope a query for subscriptions that are not on trial.
     */
    public function scopeNotOnTrial($query): void;

    /**
     * Get the trial end date.
     */
    public function trialEndsAt(): ?Carbon;

    /**
     * Get the subscription's end date.
     */
    public function endsAt(): ?Carbon;

    /**
     * Cancel the subscription.
     */
    public function cancel(): self;

    /**
     * Get the plan ID associated with this subscription.
     */
    public function planId(): mixed;

    /**
     * Check if the subscription has a specific plan.
     */
    public function hasPrice(mixed $plan): bool;

    /**
     * Check if the subscription is valid (active, on trial, or on grace period).
     */
    public function valid(): bool;

    /**
     * Check if the subscription has incomplete payment.
     */
    public function hasIncompletePayment(): bool;
}
