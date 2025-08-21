<?php

namespace App\Syllaby\Subscriptions\Enums;

/**
 * Google Play Voided Purchase Refund Types
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference
 */
enum GooglePlayVoidRefundType: int
{
    case FULL_REFUND = 1;
    case QUANTITY_BASED_PARTIAL_REFUND = 2;

    /**
     * Get the string representation.
     */
    public function toString(): string
    {
        return match ($this) {
            self::FULL_REFUND => 'full_refund',
            self::QUANTITY_BASED_PARTIAL_REFUND => 'partial_refund',
        };
    }

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FULL_REFUND => 'Full Refund',
            self::QUANTITY_BASED_PARTIAL_REFUND => 'Partial Refund (Quantity-Based)',
        };
    }

    /**
     * Check if this is a full refund.
     */
    public function isFullRefund(): bool
    {
        return $this === self::FULL_REFUND;
    }

    /**
     * Check if this is a partial refund.
     */
    public function isPartialRefund(): bool
    {
        return $this === self::QUANTITY_BASED_PARTIAL_REFUND;
    }
}
