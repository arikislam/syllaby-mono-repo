<?php

namespace App\Syllaby\Clonables\Enums;

enum CloneStatus: string
{
    case FAILED = 'failed';
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';
    case COMPLETED = 'completed';
}
