<?php

namespace App\Syllaby\Subscriptions\Enums;

enum JVZooTransactionType: string
{
    case SALE = 'SALE';
    case BILL = 'BILL';
    case RFND = 'RFND';
    case CGBK = 'CGBK';
    case INSF = 'INSF';
    case CANCEL_REBILL = 'CANCEL-REBILL';

    /**
     * Get the human-readable label for the transaction type.
     */
    public function label(): string
    {
        return match ($this) {
            self::SALE => 'Sale',
            self::BILL => 'Recurring Payment',
            self::RFND => 'Refund',
            self::CGBK => 'Chargeback',
            self::INSF => 'Insufficient Funds',
            self::CANCEL_REBILL => 'Cancel Rebill',
        };
    }

    /**
     * Check if this transaction type represents a successful payment.
     */
    public function isPayment(): bool
    {
        return in_array($this, [self::SALE, self::BILL]);
    }

    /**
     * Check if this transaction type represents a refund or chargeback.
     */
    public function isRefund(): bool
    {
        return in_array($this, [self::RFND, self::CGBK]);
    }

    /**
     * Check if this transaction type represents a cancellation.
     */
    public function isCancellation(): bool
    {
        return in_array($this, [self::CANCEL_REBILL, self::INSF]);
    }

    /**
     * Check if the given transaction type is valid.
     */
    public static function isValid(string $transactionType): bool
    {
        return self::tryFrom($transactionType) !== null;
    }
}
