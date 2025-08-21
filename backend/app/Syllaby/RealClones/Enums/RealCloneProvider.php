<?php

namespace App\Syllaby\RealClones\Enums;

enum RealCloneProvider: string
{
    case D_ID = 'd-id';
    case HEYGEN = 'heygen';
    case FASTVIDEO = 'fastvideo';

    /**
     * Convert the list of digital twin providers into an array.
     */
    public static function toArray(): array
    {
        return collect(self::cases())->pluck('value')->toArray();
    }
}
