<?php

namespace App\Syllaby\Subscriptions\Enums;

enum PlanType: string
{
    case DAY = 'day';
    case MONTH = 'month';
    case YEAR = 'year';

    /**
     * Plan label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DAY => 'daily',
            self::MONTH => 'monthly',
            self::YEAR => 'yearly',
        };
    }
}
