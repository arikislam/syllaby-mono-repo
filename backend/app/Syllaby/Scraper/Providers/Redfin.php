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

class Redfin implements ContentProvider, Throttleable
{
    public function __construct(protected ScraperContract $scraper) {}

    /**
     * Extract images from the URL
     */
    public function extractImages(string $url): array
    {
        $html = $this->getRawContent($url);

        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);

        $items = $xpath->query('//div[contains(@class, "ImageCard")]//img[@class="img-card"]/@src');

        $images = [];
        foreach ($items as $item) {
            $images[] = $item->nodeValue;
        }

        return $images;
    }

    /**
     * Extract content from the URL
     */
    public function extractContent(string $url): string
    {
        $html = $this->getRawContent($url);

        return Purify::config(['HTML.Allowed' => ''])->clean($html);
    }

    /**
     * Check if the URL is supported by the provider
     */
    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'www.redfin.com' || Str::contains($host, 'redfin.com');
    }

    /**
     * Get raw HTML content from cache or scrape the URL
     */
    private function getRawContent(string $url): string
    {
        $response = $this->scraper->scrape($url, 'html', [
            'actions' => [
                ['type' => 'wait', 'milliseconds' => 2000],
                ['type' => 'click', 'selector' => 'div#photoPreviewButton button'],
                ['type' => 'wait', 'milliseconds' => 2200],
                ['type' => 'scrape'],
            ],
        ]);

        return $response->content;
    }

    public function getThrottlingConfig(): array
    {
        return [
            'key' => FireCrawl::CACHE_KEY,
            'limit' => config('services.firecrawl.rate_limit_attempts'),
            'service' => 'firecrawl',
        ];
    }
}
