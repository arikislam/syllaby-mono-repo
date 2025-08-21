<?php

namespace App\Syllaby\Videos\Enums;

use InvalidArgumentException;
use App\System\Traits\HasEnumValues;

enum Dimension: string
{
    use HasEnumValues;

    case LANDSCAPE = 'landscape';

    case PORTRAIT = 'portrait';

    case SQUARE = 'square';

    /**
     * Get the aspect ratio for each dimension.
     */
    public static function ratios(): array
    {
        return [
            self::LANDSCAPE->value => '16:9',
            self::PORTRAIT->value => '9:16',
            self::SQUARE->value => '1:1',
        ];
    }

    /**
     * Get the dimension value for the current orientation.
     */
    public function get(string $name): int
    {
        $dimensions = match ($this) {
            self::LANDSCAPE => ['width' => 1280, 'height' => 720],
            self::PORTRAIT => ['width' => 720, 'height' => 1280],
            self::SQUARE => ['width' => 720, 'height' => 720],
        };

        return $dimensions[$name] ?? throw new InvalidArgumentException("Invalid orientation: {$this->value}");
    }

    /**
     * Get the aspect ratio of the dimension.
     */
    public function getAspectRatio(): string
    {
        return match ($this) {
            self::LANDSCAPE => '16:9',
            self::PORTRAIT => '9:16',
            self::SQUARE => '1:1',
        };
    }

    /**
     * Get the Dimension enum case from the given aspect ratio.
     */
    public static function fromAspectRatio(?string $aspectRatio): self
    {
        return match ($aspectRatio) {
            '16:9' => self::LANDSCAPE,
            '1:1' => self::SQUARE,
            default => self::PORTRAIT,
        };
    }

    /**
     * Get the Dimension enum case from the given duration.
     */
    public static function fromDuration(int $duration): self
    {
        return $duration <= 60 ? self::PORTRAIT : self::LANDSCAPE;
    }
}
