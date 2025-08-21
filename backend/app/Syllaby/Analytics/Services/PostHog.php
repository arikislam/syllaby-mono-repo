<?php

namespace App\Syllaby\Analytics\Services;

use PostHog\PostHog as PostHogClient;
use App\Syllaby\Analytics\Contracts\AbTester;

class PostHog implements AbTester
{
    /**
     * Initialize PostHog in the constructor.
     */
    public function __construct()
    {
        PostHogClient::init(config('services.posthog.api_key'), [
            'host' => config('services.posthog.url'),
        ]);
    }

    /**
     * Get a feature flag value for a user.
     */
    public function getFeatureFlag(string $flag, string|int $identifier): string
    {
        return PostHogClient::getFeatureFlag($flag, $identifier) ?? 'control';
    }

    /**
     * Capture an analytics event.
     */
    public function capture(array $data): void
    {
        PostHogClient::capture($data);
    }
}
