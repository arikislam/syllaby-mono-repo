<?php

namespace App\Syllaby\Assets\Enums;

use App\System\Traits\HasEnumValues;

enum AssetType: string
{
    use HasEnumValues;

    case AUDIOS = 'audios';
    case FACELESS_BACKGROUND = 'faceless-background';
    case AI_IMAGE = 'ai-image';
    case AI_VIDEO = 'ai-video';
    case STOCK_VIDEO = 'stock-video';
    case STOCK_IMAGE = 'stock-image';
    case CUSTOM_IMAGE = 'custom-image';
    case CUSTOM_VIDEO = 'custom-video';
    case SCRAPED = 'scraped';
    case WATERMARK = 'watermark';
    case THUMBNAIL = 'thumbnail';

    public static function fromMime(string $mime, string $type = 'stock'): AssetType
    {
        if (str_starts_with($mime, 'image/')) {
            return match ($type) {
                'stock' => self::STOCK_IMAGE,
                'custom' => self::CUSTOM_IMAGE,
                'scraped' => self::SCRAPED,
                default => self::AI_IMAGE,
            };
        }

        return match ($type) {
            'stock' => self::STOCK_VIDEO,
            'custom' => self::CUSTOM_VIDEO,
            'scraped' => self::SCRAPED,
            default => self::AI_VIDEO,
        };
    }
}
