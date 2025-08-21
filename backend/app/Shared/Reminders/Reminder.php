<?php

namespace App\Shared\Reminders;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class Reminder
{
    /**
     * Set the reminder.
     */
    public static function set(string $key, Carbon $date, mixed $identifier): ?int
    {
        if ($date->isPast()) {
            return null;
        }

        return Redis::zadd($key, $date->timestamp, $identifier);
    }

    /**
     * Get the reminders.
     */
    public static function get(string $key, Carbon $start, ?Carbon $end = null): array
    {
        $end ??= $start->copy()->addMinute();

        return Redis::zrange($key, $start->timestamp, $end->timestamp, ['BYSCORE']);
    }

    /**
     * Clear the reminders.
     */
    public static function clear(string $key, Carbon $start, ?Carbon $end = null): void
    {
        $end ??= $start->copy()->addMinute();

        Redis::zremrangebyscore($key, $start->timestamp, $end->timestamp);
    }

    /**
     * Cleanup old keys.
     */
    public static function prune(string $key): void
    {
        $yesterday = now()->subDay()->timestamp;
        Redis::zremrangebyscore($key, 0, $yesterday);
    }
}
