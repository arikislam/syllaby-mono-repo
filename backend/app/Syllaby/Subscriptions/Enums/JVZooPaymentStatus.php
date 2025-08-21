<?php

namespace App\Syllaby\Subscriptions\Enums;

enum JVZooPaymentStatus: string
{
    case COMPLETED = 'COMPLETED';
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';
    case REFUNDED = 'REFUNDED';
    case CANCELLED = 'CANCELLED';

    /**
     * Get the human-readable label for the payment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completed',
            self::PENDING => 'Pending',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if this status represents a successful payment.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if this status represents a failed payment.
     */
    public function isFailed(): bool
    {
        return in_array($this, [self::FAILED, self::CANCELLED]);
    }

    /**
     * Check if this status is still processing.
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if this status represents a refunded payment.
     */
    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }
}
