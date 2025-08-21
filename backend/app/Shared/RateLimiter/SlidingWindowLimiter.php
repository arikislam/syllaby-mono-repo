<?php

namespace App\Shared\RateLimiter;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\InteractsWithTime;

class SlidingWindowLimiter
{
    use InteractsWithTime;

    public function __construct(
        protected int $maxAttempts = 60,
        protected int $decaySeconds = 60,
        protected string $prefix = 'sliding-window:'
    ) {}

    /**
     * Create a new instance with specific configuration.
     */
    public static function for(string $name, int $maxAttempts, int $decaySeconds): self
    {
        return new self($maxAttempts, $decaySeconds, "sliding-window:{$name}:");
    }

    /**
     * Increment the counter for a given key for a given decay time.
     */
    public function attempt(string $key): bool
    {
        $key = $this->prefix.$key;
        $now = $this->currentTime();
        $window = $now - $this->decaySeconds;
        $member = sprintf('%s:%s', $now, microtime(true));

        $results = Redis::transaction(function ($redis) use ($key, $now, $window, $member) {
            $redis->zremrangebyscore($key, 0, $window);
            $redis->zadd($key, $now, $member);
            $redis->expire($key, $this->decaySeconds + 1);
            $redis->zcard($key);
        });

        $currentAttempts = $results[3]; // Last command result (zcard)

        return $currentAttempts <= $this->maxAttempts;
    }

    /**
     * Clear the hits and lockout timer for the given key.
     */
    public function clear(string $key): void
    {
        Redis::del($this->prefix.$key);
    }

    /**
     * Get the number of seconds until the oldest attempt expires.
     */
    public function availableIn(string $key): int
    {
        $key = $this->prefix.$key;
        $now = $this->currentTime();
        $window = $now - $this->decaySeconds;

        $oldestTimestamp = Redis::zrangebyscore($key, $window + 1, '+inf', [
            'LIMIT' => [0, 1],
            'WITHSCORES' => true,
        ]);

        if (empty($oldestTimestamp)) {
            return 0;
        }

        $oldestScore = (int) array_values($oldestTimestamp)[0];

        $availableAt = $oldestScore + $this->decaySeconds;

        return max(0, $availableAt - $now);
    }

    /**
     * Get the maximum attempts allowed.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
}
