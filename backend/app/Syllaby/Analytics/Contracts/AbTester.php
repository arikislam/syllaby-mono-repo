<?php

namespace App\Syllaby\Analytics\Contracts;

interface AbTester
{
    /**
     * Get a feature flag value for a user.
     */
    public function getFeatureFlag(string $flag, string|int $identifier): string;

    /**
     * Capture an analytics event.
     */
    public function capture(array $data): void;
}
