<?php

namespace App\Syllaby\Credits\Enums;

enum CreditEventTypeEnum: int
{
    case ADDED = 1;
    case SPEND = 2;

    public static function find(string $name): CreditEventTypeEnum
    {
        foreach (self::cases() as $eventType) {
            if ($name == $eventType->value) {
                return $eventType;
            }
        }
    }

    public function details(): string
    {
        return match ($this) {
            self::ADDED => 'Added new',
            self::SPEND => 'Spent',
        };
    }
}
