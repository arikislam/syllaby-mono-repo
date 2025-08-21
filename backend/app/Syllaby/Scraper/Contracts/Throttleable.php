<?php

namespace App\Syllaby\Scraper\Contracts;

interface Throttleable
{
    /**
     * Get throttling configuration for this provider
     *
     * @return array{key: string, attempts: int, service: string} Throttling configuration
     */
    public function getThrottlingConfig(): array;
}
