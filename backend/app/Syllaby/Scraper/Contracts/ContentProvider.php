<?php

namespace App\Syllaby\Scraper\Contracts;

interface ContentProvider
{
    /**
     * Extract images from a source URL using any appropriate method
     *
     * @param  string  $url  The URL to extract images from
     * @return array<string> List of image URLs
     */
    public function extractImages(string $url): array;

    /**
     * Extract content for script generation from a source URL
     *
     * @param  string  $url  The URL to extract content from
     * @return string The content for script generation
     */
    public function extractContent(string $url): string;

    /**
     * Check if this provider supports the given URL
     *
     * @param  string  $url  The URL to check
     * @return bool Whether this provider supports the URL
     */
    public function supports(string $url): bool;
}
