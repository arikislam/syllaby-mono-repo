<?php

namespace App\Syllaby\Speeches\Enums;

enum SpeechStatus: string
{
    case COMPLETED = 'completed';
    case PROCESSING = 'processing';
    case FAILED = 'failed';
}
