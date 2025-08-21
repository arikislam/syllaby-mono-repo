<?php

namespace App\Syllaby\Schedulers\Enums;

use App\System\Traits\HasEnumValues;

enum SchedulerStatus: string
{
    use HasEnumValues;

    case DRAFT = 'draft';
    case FAILED = 'failed';
    case PAUSED = 'paused';
    case WRITING = 'writing';
    case DELETED = 'deleted';
    case COMPLETED = 'completed';
    case REVIEWING = 'reviewing';
    case SCHEDULED = 'scheduled';
    case PUBLISHING = 'publishing';
    case GENERATING = 'generating';
}
