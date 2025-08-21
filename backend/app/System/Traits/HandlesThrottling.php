<?php

namespace App\System\Traits;

use Str;
use RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

trait HandlesThrottling
{
    public function ensureIsThrottled(string $key, int $allowed, string $platform): void
    {
        if (RateLimiter::tooManyAttempts($key, $allowed)) {
            Log::critical(sprintf('%s global rate limit exceeded. Subsequent requests will be throttled.', Str::ucfirst($platform)));
            throw ValidationException::withMessages(['message' => __('auth.rate_limit_exceeded')])->status(Response::HTTP_TOO_MANY_REQUESTS);
        }

        $attempts = RateLimiter::attempts($key);

        if ($attempts > $allowed - 5 && $attempts < $allowed) {
            Log::warning(sprintf('Proactive alert - %s global rate limit warning', Str::ucfirst($platform)));
        }

        RateLimiter::hit($key);
    }
}
