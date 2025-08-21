<?php

namespace App\Syllaby\Videos\Enums;

use Illuminate\Support\Arr;
use App\System\Traits\HasEnumValues;

enum TextPosition: string
{
    use HasEnumValues;

    case TOP = 'top';
    case BOTTOM = 'bottom';
    case CENTER = 'center';

    /**
     * Get the coordinates for the text position
     */
    public function coordinates(string $axis, string $orientation = 'portrait'): ?string
    {
        $coordinates = match ($this) {
            self::TOP => [
                Dimension::PORTRAIT->value => [
                    'x' => '50%', 'y' => '0%',
                ],
                Dimension::LANDSCAPE->value => [
                    'x' => '50%', 'y' => '0%',
                ],
                Dimension::SQUARE->value => [
                    'x' => '50%', 'y' => '0%',
                ],
            ],

            self::BOTTOM => [
                Dimension::PORTRAIT->value => [
                    'x' => '50%', 'y' => '86%',
                ],
                Dimension::LANDSCAPE->value => [
                    'x' => '50%', 'y' => '100%',
                ],
                Dimension::SQUARE->value => [
                    'x' => '50%', 'y' => '100%',
                ],
            ],

            self::CENTER => [
                Dimension::PORTRAIT->value => [
                    'x' => '50%', 'y' => '50%',
                ],
                Dimension::LANDSCAPE->value => [
                    'x' => '50%', 'y' => '50%',
                ],
                Dimension::SQUARE->value => [
                    'x' => '50%', 'y' => '50%',
                ],
            ],
        };

        return Arr::get($coordinates, "{$orientation}.{$axis}");
    }
}
