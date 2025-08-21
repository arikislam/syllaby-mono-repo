<?php

namespace App\Syllaby\Ideas\Enums;

enum Networks: string
{
    case GOOGLE = 'google';
    case GOOGLE_TRENDS = 'google-trends';
    case YOUTUBE = 'youtube';
    case INSTAGRAM = 'instagram';
    case TWITTER = 'twitter';
    case BING = 'bing';
    case PINTEREST = 'pinterest';
    case TIKTOK = 'tiktok';

    /**
     * Convert the list of keyword search sources into an array.
     */
    public static function toArray(): array
    {
        return array_map(fn ($item) => $item->value, self::cases());
    }

    /**
     * Get the default result type for each network.
     */
    public static function type(string $network): string
    {
        return match ($network) {
            self::GOOGLE_TRENDS->value => 'top',
            self::INSTAGRAM->value, self::TWITTER->value => 'hashtags',
            self::PINTEREST->value, self::TIKTOK->value => 'suggestions',
            self::GOOGLE->value, self::YOUTUBE->value, self::BING->value => 'questions',
        };
    }
}
