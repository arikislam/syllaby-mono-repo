<?php

namespace App\Syllaby\Scraper\Contracts;

use App\Syllaby\Scraper\DTOs\ScraperResponseData;

interface ScraperContract
{
    /**
     * Scrapes the given URL and returns the data
     */
    public function scrape(string $url, string $format, array $options = [], bool $fresh = false): ScraperResponseData;
}
