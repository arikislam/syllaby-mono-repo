<?php

namespace App\Syllaby\Schedulers\Enums;

use App\System\Traits\HasEnumValues;

enum SchedulerSource: string
{
    use HasEnumValues;

    case AI = 'ai';
    case CSV = 'csv';
    case MANUAL = 'manual';
}
