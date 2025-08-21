<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\Relation;

/** @throws Throwable */
function attempt(Closure $action, bool $transaction = true, int $times = 40, int $delay = 250): mixed
{
    $closure = $transaction ? fn () => DB::transaction($action, 10) : $action;

    return retry($times, $closure, $delay);
}

function format_bytes($bytes, $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision).' '.$units[$pow];
}

function real_clone_credits(string $provider, string $script): float|int
{
    $wordsPerMinute = 183;
    $secondsPerInterval = 30;
    $words = Str::wordCount($script);
    $duration = round(($words / $wordsPerMinute) * (1 / 60));

    $creditsPerMinute = config("credit-engine.video.{$provider}");
    $intervals = ceil(max($duration, 30) / $secondsPerInterval);

    return ($creditsPerMinute * $intervals) / 2 ?? PHP_INT_MAX;
}

function video_render_credits(string $provider, int $duration): float|int
{
    $secondsPerInterval = 30;
    $creditsPerMinute = config("credit-engine.video.{$provider}");
    $intervals = ceil(max($duration, 30) / $secondsPerInterval);

    return ($creditsPerMinute * $intervals) / 2 ?? PHP_INT_MAX;
}

function image_render_credits(string $provider, int $duration): float|int
{
    $secondsPerInterval = 30;
    $creditsPer30Seconds = ceil(config("credit-engine.images.{$provider}") / 2);
    $intervals = ceil(max($duration, 30) / $secondsPerInterval);

    return $creditsPer30Seconds * $intervals ?? PHP_INT_MAX;
}

function reading_time(string $text, int $wpm, string $language = 'en'): float|int
{
    $pauses = 0;
    $regex = '/\[pause\s+(\d+(?:\.\d+)?)(ms|s)?\]/';

    $normalized = preg_replace_callback($regex, function ($matches) use (&$pauses) {
        $value = (float) Arr::get($matches, 1, 0);
        $unit = Arr::get($matches, 2, 's');

        if ($unit === 'ms') {
            $value /= 1000;
        }

        $pauses += $value;

        return '';
    }, $text);

    $words = word_count($normalized, $language);

    return round((($words / $wpm) * 60) + $pauses);
}

function word_count(string $text, string $language = 'en'): int
{
    if (! $text = trim($text)) {
        return 0;
    }

    $iterator = IntlBreakIterator::createWordInstance($language);
    $iterator->setText($text);

    $count = 0;
    foreach ($iterator->getPartsIterator() as $part) {
        if (preg_match('/\w/', $part)) {
            $count++;
        }
    }

    return $count;
}

function morph_type(string $type, string $class): bool
{
    return $type === Relation::getMorphAlias($class);
}

function words_count(int $duration): string
{
    return match (true) {
        $duration <= 60 => '110-130',
        $duration <= 180 => '380-420',
        $duration <= 300 => '750-900',
        $duration <= 600 => '1500-1800',
        default => $duration * 3
    };
}
