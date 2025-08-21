<?php

namespace App\Syllaby\Publisher\Publications\Enums;

enum TikTokPrivacyStatus
{
    case PUBLIC_TO_EVERYONE;
    case MUTUAL_FOLLOW_FRIENDS;
    case FOLLOWER_OF_CREATOR;
    case SELF_ONLY;

    public static function values(): array
    {
        return array_column(self::cases(), 'name');
    }
}
