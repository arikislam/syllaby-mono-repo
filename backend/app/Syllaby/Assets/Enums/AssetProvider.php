<?php

namespace App\Syllaby\Assets\Enums;

use App\System\Traits\HasEnumValues;

enum AssetProvider: string
{
    use HasEnumValues;

    case MINIMAX = 'minimax';
    case REPLICATE = 'replicate';
    case PEXELS = 'pexels';
    case CUSTOM = 'custom';
    case SYLLABY = 'syllaby';
}
