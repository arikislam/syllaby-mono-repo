<?php

namespace App\Syllaby\Videos\Enums;

use Arr;
use App\System\Traits\HasEnumValues;

enum Transition: string
{
    use HasEnumValues;

    case SLIDE_LEFT = 'slide-left';
    case SLIDE_RIGHT = 'slide-right';
    case SLIDE_UP = 'slide-up';
    case SLIDE_DOWN = 'slide-down';

    case SCALE = 'scale';
    case SCALE_LEFT = 'scale-left';
    case SCALE_RIGHT = 'scale-right';
    case SCALE_UP = 'scale-up';
    case SCALE_DOWN = 'scale-down';

    case FADE = 'fade';
    case NONE = 'none';
    case MIXED = 'mixed';

    public static function all(): array
    {
        return [
            self::SLIDE_LEFT->value => 'Slide Left',
            self::SLIDE_RIGHT->value => 'Slide Right',
            self::SLIDE_UP->value => 'Slide Up',
            self::SLIDE_DOWN->value => 'Slide Down',
            self::SCALE->value => 'Scale',
            self::SCALE_LEFT->value => 'Scale Left',
            self::SCALE_RIGHT->value => 'Scale Right',
            self::SCALE_UP->value => 'Scale Up',
            self::SCALE_DOWN->value => 'Scale Down',
            self::FADE->value => 'Fade',
            self::MIXED->value => 'Mixed (Random)',
        ];
    }

    public function getConfig(): array
    {
        static $resolved = [];

        if ($this == self::MIXED) { // Don't cache mixed transitions as we want to randomize them
            return $this->mixed();
        }

        if (isset($resolved[$this->value])) {
            return $resolved[$this->value];
        }

        $config = match ($this) {
            self::SLIDE_LEFT => $this->slideLeft(),
            self::SLIDE_RIGHT => $this->slideRight(),
            self::SLIDE_UP => $this->slideUp(),
            self::SLIDE_DOWN => $this->slideDown(),
            self::SCALE => $this->scale(),
            self::SCALE_LEFT => $this->scaleLeft(),
            self::SCALE_RIGHT => $this->scaleRight(),
            self::SCALE_UP => $this->scaleUp(),
            self::SCALE_DOWN => $this->scaleDown(),
            self::FADE => $this->fade(),
            default => []
        };

        return tap($config, function ($config) use (&$resolved) {
            $resolved[$this->value] = $config;
        });
    }

    private function slideLeft(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'slide',
            'direction' => '180°',
        ];
    }

    private function slideRight(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'slide',
            'direction' => '0°',
        ];
    }

    private function slideUp(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'slide',
            'direction' => '90°',
        ];
    }

    private function slideDown(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'slide',
            'direction' => '270°',
        ];
    }

    private function scale(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'scale',
            'x_anchor' => '50%',
            'y_anchor' => '50%',
        ];
    }

    private function scaleLeft(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'scale',
            'x_anchor' => '100%',
            'y_anchor' => '50%',
            'axis' => 'both',
        ];
    }

    private function scaleRight(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'scale',
            'x_anchor' => '0%',
            'y_anchor' => '50%',
            'axis' => 'both',
        ];
    }

    private function scaleUp(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'scale',
            'x_anchor' => '50%',
            'y_anchor' => '100%',
            'axis' => 'both',
        ];
    }

    private function scaleDown(): array
    {
        return [
            'time' => 0,
            'duration' => 0.5,
            'transition' => true,
            'type' => 'scale',
            'x_anchor' => '50%',
            'y_anchor' => '0%',
            'axis' => 'both',
        ];
    }

    private function fade(): array
    {
        return [
            'time' => 0,
            'duration' => 0.6,
            'transition' => true,
            'type' => 'fade',
        ];
    }

    private function mixed(): array
    {
        $transitions = collect(Transition::cases())
            ->reject(fn ($transition) => $transition === self::MIXED || $transition === self::NONE)
            ->all();

        return Arr::random($transitions)->getConfig();
    }
}
