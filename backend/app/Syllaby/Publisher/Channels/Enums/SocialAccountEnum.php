<?php

namespace App\Syllaby\Publisher\Channels\Enums;

use InvalidArgumentException;

enum SocialAccountEnum: int
{
    case Youtube = 0;
    case TikTok = 1;
    case Google = 2;
    case LinkedIn = 3;
    case Facebook = 4;
    case Instagram = 5;
    case Threads = 6;

    public static function fromString(string $provider): ?self
    {
        return match ($provider) {
            'youtube' => self::Youtube,
            'tiktok' => self::TikTok,
            'linkedin' => self::LinkedIn,
            'google' => self::Google,
            'facebook' => self::Facebook,
            'instagram' => self::Instagram,
            'threads' => self::Threads,
            default => throw new InvalidArgumentException('Invalid Social Account')
        };
    }

    public function toString(): string
    {
        return str($this->name)->lower()->toString();
    }

    public static function channels(): array
    {
        return array_map(fn ($value) => $value->toString(), array_filter(self::cases(), function ($value) {
            return $value !== self::Google;
        }));
    }
}
