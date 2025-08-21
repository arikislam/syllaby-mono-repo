<?php

namespace App\Syllaby\Videos\Enums;

enum VideoStatus: string
{
    case DRAFT = 'draft';
    case FAILED = 'failed';
    case SYNCING = 'syncing';
    case RENDERING = 'rendering';
    case COMPLETED = 'completed';
    case SYNC_FAILED = 'sync-failed';
    case TIMEOUT = 'timeout';
    case MODIFYING = 'modifying';
    case MODIFIED = 'modified';

    public function isCompleted(): bool
    {
        return in_array($this->value, [self::COMPLETED->value, self::MODIFIED->value]);
    }
}
