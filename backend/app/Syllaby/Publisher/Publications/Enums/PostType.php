<?php

namespace App\Syllaby\Publisher\Publications\Enums;

enum PostType: string
{
    case REEL = 'reel';
    case SHORT = 'short';
    case STORY = 'story';
    case POST = 'post';

    public function toString(): string
    {
        return $this->value;
    }

    public static function facebook(): array
    {
        return [
            self::POST->value,
            self::STORY->value,
            self::REEL->value
        ];
    }

    public static function instagram(): array
    {
        return [
            self::STORY->value,
            self::REEL->value,
        ];
    }
}