<?php

namespace App\Syllaby\Videos\Enums;

use InvalidArgumentException;
use App\System\Traits\HasEnumValues;

enum WatermarkPosition: string
{
    use HasEnumValues;

    case TOP_LEFT = 'top-left';
    case TOP_CENTER = 'top-center';
    case TOP_RIGHT = 'top-right';

    case MIDDLE_LEFT = 'middle-left';
    case MIDDLE_CENTER = 'middle-center';
    case MIDDLE_RIGHT = 'middle-right';

    case BOTTOM_LEFT = 'bottom-left';
    case BOTTOM_CENTER = 'bottom-center';
    case BOTTOM_RIGHT = 'bottom-right';

    case NONE = 'none';

    public function coordinates(string $axis): ?string
    {
        $coordinates = match ($this) {
            self::TOP_LEFT => ['x' => 0, 'y' => 0],
            self::TOP_CENTER => ['x' => 50, 'y' => 0],
            self::TOP_RIGHT => ['x' => 100, 'y' => 0],
            self::MIDDLE_LEFT => ['x' => 0, 'y' => 50],
            self::MIDDLE_CENTER => ['x' => 50, 'y' => 50],
            self::MIDDLE_RIGHT => ['x' => 100, 'y' => 50],
            self::BOTTOM_LEFT => ['x' => 0, 'y' => 100],
            self::BOTTOM_CENTER => ['x' => 50, 'y' => 100],
            self::BOTTOM_RIGHT => ['x' => 100, 'y' => 100],
            default => ['x' => null, 'y' => null],
        };

        return $coordinates[$axis].'%' ?? throw new InvalidArgumentException("Invalid axis: {$axis}");
    }
}
