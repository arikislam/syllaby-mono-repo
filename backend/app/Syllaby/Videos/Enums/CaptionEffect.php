<?php

namespace App\Syllaby\Videos\Enums;

use App\System\Traits\HasEnumValues;

enum CaptionEffect: string
{
    use HasEnumValues;

    case KARAOKE = 'karaoke';
    case HIGHLIGHT = 'highlight';
    case FADE = 'fade';
    case BOUNCE = 'bounce';
    case SLIDE = 'slide';
    case ENLARGE = 'enlarge';

    public static function all(): array
    {
        return [
            self::KARAOKE->value => 'Karaoke',
            self::HIGHLIGHT->value => 'Highlight',
            self::FADE->value => 'Fade',
            self::BOUNCE->value => 'Bounce',
            self::SLIDE->value => 'Slide',
            self::ENLARGE->value => 'Enlarge',
        ];
    }
}
