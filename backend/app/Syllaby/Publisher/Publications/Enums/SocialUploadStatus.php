<?php

namespace App\Syllaby\Publisher\Publications\Enums;

enum SocialUploadStatus: string
{
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case SCHEDULED = 'scheduled';
    case DRAFT = 'draft';
    case REMOVED_BY_USER = 'removed_by_user';

    public static function unpublished(): array
    {
        return [
            self::SCHEDULED->value,
            self::FAILED->value,
            self::DRAFT->value,
            self::PROCESSING->value
        ];
    }
}
