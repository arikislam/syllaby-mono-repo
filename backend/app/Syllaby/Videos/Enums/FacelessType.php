<?php

namespace App\Syllaby\Videos\Enums;

use App\System\Traits\HasEnumValues;

enum FacelessType: string
{
    use HasEnumValues;

    case B_ROLL = 'b-roll';
    case URL_BASED = 'url-based';
    case AI_VISUALS = 'ai-visuals';
    case SINGLE_CLIP = 'single-clip';
}
