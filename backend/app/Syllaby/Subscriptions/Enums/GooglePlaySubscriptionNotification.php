<?php

namespace App\Syllaby\Subscriptions\Enums;

/**
 * Google Play Subscription Notification Types
 *
 * Maps integer notification types from Google Play RTDN to string representations
 * stored in the database.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference
 */
enum GooglePlaySubscriptionNotification: int
{
    case SUBSCRIPTION_RECOVERED = 1;
    case SUBSCRIPTION_RENEWED = 2;
    case SUBSCRIPTION_CANCELED = 3;
    case SUBSCRIPTION_PURCHASED = 4;
    case SUBSCRIPTION_ON_HOLD = 5;
    case SUBSCRIPTION_IN_GRACE_PERIOD = 6;
    case SUBSCRIPTION_RESTARTED = 7;
    case SUBSCRIPTION_PRICE_CHANGE_CONFIRMED = 8;
    case SUBSCRIPTION_DEFERRED = 9;
    case SUBSCRIPTION_PAUSED = 10;
    case SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED = 11;
    case SUBSCRIPTION_REVOKED = 12;
    case SUBSCRIPTION_EXPIRED = 13;

    /**
     * Get the string representation for database storage.
     */
    public function toString(): string
    {
        return match ($this) {
            self::SUBSCRIPTION_RECOVERED => 'subscription.recovered',
            self::SUBSCRIPTION_RENEWED => 'subscription.renewed',
            self::SUBSCRIPTION_CANCELED => 'subscription.canceled',
            self::SUBSCRIPTION_PURCHASED => 'subscription.purchased',
            self::SUBSCRIPTION_ON_HOLD => 'subscription.on_hold',
            self::SUBSCRIPTION_IN_GRACE_PERIOD => 'subscription.in_grace_period',
            self::SUBSCRIPTION_RESTARTED => 'subscription.restarted',
            self::SUBSCRIPTION_PRICE_CHANGE_CONFIRMED => 'subscription.price_change_confirmed',
            self::SUBSCRIPTION_DEFERRED => 'subscription.deferred',
            self::SUBSCRIPTION_PAUSED => 'subscription.paused',
            self::SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED => 'subscription.pause_schedule_changed',
            self::SUBSCRIPTION_REVOKED => 'subscription.revoked',
            self::SUBSCRIPTION_EXPIRED => 'subscription.expired',
        };
    }

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUBSCRIPTION_RECOVERED => 'Subscription Recovered',
            self::SUBSCRIPTION_RENEWED => 'Subscription Renewed',
            self::SUBSCRIPTION_CANCELED => 'Subscription Canceled',
            self::SUBSCRIPTION_PURCHASED => 'Subscription Purchased',
            self::SUBSCRIPTION_ON_HOLD => 'Subscription On Hold',
            self::SUBSCRIPTION_IN_GRACE_PERIOD => 'Subscription In Grace Period',
            self::SUBSCRIPTION_RESTARTED => 'Subscription Restarted',
            self::SUBSCRIPTION_PRICE_CHANGE_CONFIRMED => 'Price Change Confirmed',
            self::SUBSCRIPTION_DEFERRED => 'Subscription Deferred',
            self::SUBSCRIPTION_PAUSED => 'Subscription Paused',
            self::SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED => 'Pause Schedule Changed',
            self::SUBSCRIPTION_REVOKED => 'Subscription Revoked',
            self::SUBSCRIPTION_EXPIRED => 'Subscription Expired',
        };
    }

    /**
     * Create enum from string representation.
     */
    public static function fromString(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->toString() === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Check if this notification indicates a new purchase.
     */
    public function isPurchase(): bool
    {
        return $this === self::SUBSCRIPTION_PURCHASED;
    }

    /**
     * Check if this notification indicates a cancellation or termination.
     */
    public function isCancellation(): bool
    {
        return in_array($this, [
            self::SUBSCRIPTION_CANCELED,
            self::SUBSCRIPTION_EXPIRED,
            self::SUBSCRIPTION_REVOKED,
        ]);
    }

    /**
     * Check if this notification indicates a renewal.
     */
    public function isRenewal(): bool
    {
        return in_array($this, [
            self::SUBSCRIPTION_RENEWED,
            self::SUBSCRIPTION_RECOVERED,
            self::SUBSCRIPTION_RESTARTED,
        ]);
    }

    /**
     * Check if this notification indicates a pause/hold state.
     */
    public function isPaused(): bool
    {
        return in_array($this, [
            self::SUBSCRIPTION_PAUSED,
            self::SUBSCRIPTION_ON_HOLD,
            self::SUBSCRIPTION_IN_GRACE_PERIOD,
        ]);
    }

    /**
     * Get all subscription notification type strings.
     */
    public static function getAllStrings(): array
    {
        return array_map(fn ($case) => $case->toString(), self::cases());
    }

    /**
     * Get purchase notification type strings.
     */
    public static function getPurchaseStrings(): array
    {
        return [self::SUBSCRIPTION_PURCHASED->toString()];
    }

    /**
     * Get cancellation notification type strings.
     */
    public static function getCancellationStrings(): array
    {
        return [
            self::SUBSCRIPTION_CANCELED->toString(),
            self::SUBSCRIPTION_EXPIRED->toString(),
            self::SUBSCRIPTION_REVOKED->toString(),
        ];
    }
}
