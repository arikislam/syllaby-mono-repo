<?php

namespace App\Syllaby\Scraper\Providers;

use DOMXPath;
use DOMDocument;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;
use App\Syllaby\Scraper\Vendors\FireCrawl;
use App\Syllaby\Scraper\Contracts\Throttleable;
use App\Syllaby\Scraper\Contracts\ContentProvider;
use App\Syllaby\Scraper\Contracts\ScraperContract;

class Fallback implements ContentProvider, Throttleable
{
    public function __construct(protected ScraperContract $scraper) {}

    public function extractImages(string $url): array
    {
        $html = $this->getRawContent($url, 'html');

        $dom = new DOMDocument;

        @$dom->loadHTML($html, LIBXML_NOERROR);

        $xpath = new DOMXPath($dom);

        $images = $xpath->query('//img/@src');

        $links = [];

        foreach ($images as $image) {
            if (Str::startsWith($image->nodeValue, 'http') && $this->isProductImage($image->nodeValue)) {
                $links[] = $image->nodeValue;
            }
        }

        return array_filter($links);
    }

    public function extractContent(string $url): string
    {
        $raw = $this->getRawContent($url, 'html');

        return Purify::config(['HTML.Allowed' => ''])->clean($raw);
    }

    public function supports(string $url): bool
    {
        // Since this is a generic provider, it should support all URLs
        // This should be called last in the list of providers
        return true;
    }

    public function getThrottlingConfig(): array
    {
        return [
            'key' => FireCrawl::CACHE_KEY,
            'limit' => config('services.firecrawl.rate_limit_attempts'),
            'service' => 'firecrawl',
        ];
    }

    /**
     * Get raw HTML content from cache or scraper
     */
    private function getRawContent(string $url, string $format): string
    {
        $response = $this->scraper->scrape($url, $format);

        return $response->content;
    }

    private function isProductImage(mixed $nodeValue): bool
    {
        $path = parse_url($nodeValue, PHP_URL_PATH);

        return ! Str::endsWith($path, '.svg');
    }
}
