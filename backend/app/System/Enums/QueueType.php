<?php

namespace App\System\Enums;

use App\System\Traits\HasEnumValues;

enum QueueType: string
{
    use HasEnumValues;

    case EMAIL = 'emails';
    case RENDER = 'render';
    case DEFAULT = 'default';
    case PUBLISH = 'publish';
    case FACELESS = 'faceless';
    case VIDEO_SCRIPT = 'video_script';
    case ACCOUNT_DELETION = 'account_deletion';
}
