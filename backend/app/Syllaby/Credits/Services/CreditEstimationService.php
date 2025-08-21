<?php

namespace App\Syllaby\Credits\Services;

use Illuminate\Support\Arr;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class CreditEstimationService
{
    public static function speech(): int
    {
        return config('credit-engine.audio.elevenlabs');
    }

    public static function idea(): int
    {
        return static::extract(CreditEventEnum::IDEA_DISCOVERED) ?? PHP_INT_MAX;
    }

    public static function script(): int
    {
        return static::extract(CreditEventEnum::CONTENT_PROMPT_REQUESTED) ?? PHP_INT_MAX;
    }

    public static function video(string $provider): int
    {
        return config("credit-engine.video.{$provider}") / 2;
    }

    public static function faceless(string $provider, string $script, bool $ai = false): int
    {
        return 6 + config('credit-engine.audio.elevenlabs');
    }

    private static function extract(CreditEventEnum $type): int
    {
        $events = config('credit-engine.events');

        return Arr::get($events, "{$type->value}.min_amount");
    }
}
