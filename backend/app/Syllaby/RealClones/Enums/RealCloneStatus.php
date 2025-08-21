<?php

namespace App\Syllaby\RealClones\Enums;

enum RealCloneStatus: string
{
    case DRAFT = 'draft';
    case FAILED = 'failed';
    case SYNCING = 'syncing';
    case COMPLETED = 'completed';
    case GENERATING = 'generating';
    case SYNC_FAILED = 'sync-failed';
}
