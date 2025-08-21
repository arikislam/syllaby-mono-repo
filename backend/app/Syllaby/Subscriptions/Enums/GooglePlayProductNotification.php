<?php

namespace App\Syllaby\Subscriptions\Enums;

/**
 * Google Play One-Time Product Notification Types
 *
 * Maps integer notification types from Google Play RTDN to string representations
 * stored in the database.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference
 */
enum GooglePlayProductNotification: int
{
    case ONE_TIME_PRODUCT_PURCHASED = 1;
    case ONE_TIME_PRODUCT_CANCELED = 2;

    /**
     * Get the string representation for database storage.
     */
    public function toString(): string
    {
        return match ($this) {
            self::ONE_TIME_PRODUCT_PURCHASED => 'product.purchased',
            self::ONE_TIME_PRODUCT_CANCELED => 'product.canceled',
        };
    }

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ONE_TIME_PRODUCT_PURCHASED => 'Product Purchased',
            self::ONE_TIME_PRODUCT_CANCELED => 'Product Canceled',
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
        return $this === self::ONE_TIME_PRODUCT_PURCHASED;
    }

    /**
     * Check if this notification indicates a cancellation.
     */
    public function isCancellation(): bool
    {
        return $this === self::ONE_TIME_PRODUCT_CANCELED;
    }

    /**
     * Get all product notification type strings.
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
        return [self::ONE_TIME_PRODUCT_PURCHASED->toString()];
    }

    /**
     * Get cancellation notification type strings.
     */
    public static function getCancellationStrings(): array
    {
        return [self::ONE_TIME_PRODUCT_CANCELED->toString()];
    }
}
