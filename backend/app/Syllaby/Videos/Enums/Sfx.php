<?php

namespace App\Syllaby\Videos\Enums;

use App\System\Traits\HasEnumValues;

enum Sfx: string
{
    use HasEnumValues;

    case NONE = 'none';
    case WHOOSH = 'whoosh';

    /**
     * Sound effect source url.
     */
    public function url(): string
    {
        return match ($this) {
            self::NONE => 'none',
            self::WHOOSH => 'https://syllaby-assets.sfo3.cdn.digitaloceanspaces.com/faceless/sfx/whoosh-fast-light.mp3',
        };
    }
}
