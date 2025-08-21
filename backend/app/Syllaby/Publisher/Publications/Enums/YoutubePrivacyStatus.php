<?php

namespace App\Syllaby\Publisher\Publications\Enums;

use App\System\Traits\HasEnumValues;

enum YoutubePrivacyStatus: string
{
    use HasEnumValues;

    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case UNLISTED = 'unlisted';
}
