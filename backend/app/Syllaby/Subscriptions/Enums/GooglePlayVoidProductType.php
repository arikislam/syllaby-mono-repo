<?php

namespace App\Syllaby\Subscriptions\Enums;

/**
 * Google Play Voided Purchase Product Types
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference
 */
enum GooglePlayVoidProductType: int
{
    case SUBSCRIPTION = 1;
    case ONE_TIME = 2;

    /**
     * Get the string representation.
     */
    public function toString(): string
    {
        return match ($this) {
            self::SUBSCRIPTION => 'subscription',
            self::ONE_TIME => 'one_time',
        };
    }

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUBSCRIPTION => 'Subscription',
            self::ONE_TIME => 'One-Time Product',
        };
    }
}
