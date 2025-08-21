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

class Ebay implements ContentProvider, Throttleable
{
    public function __construct(protected ScraperContract $scraper) {}

    public function extractImages(string $url): array
    {
        $html = $this->getRawContent($url);

        $dom = new DOMDocument;

        $dom->loadHTML($html, LIBXML_NOERROR);

        $xpath = new DOMXPath($dom);

        $images = $xpath->query("//div[contains(@class, 'ux-image-carousel-container') and contains(@class, 'image-container')]//img");
        $sources = [];

        foreach ($images as $image) {
            $source = $image->getAttribute('data-zoom-src') ?? $image->getAttribute('data-src') ?? $this->parseSrcset($image->getAttribute('data-srcset'));

            if (filled($source)) {
                $sources[] = $source;
            }
        }

        if (empty($sources)) {
            $sources = $this->getFromFallback($xpath);
        }

        return $sources;
    }

    /**
     * Extract content from the URL
     */
    public function extractContent(string $url): string
    {
        $html = $this->getRawContent($url);

        return Purify::config(['HTML.Allowed' => ''])->clean($html);
    }

    private function getRawContent(string $url): string
    {
        $response = $this->scraper->scrape($url, 'rawHtml', [
            'includeTags' => ['script'],
            'onlyMainContent' => false,
            'actions' => [
                ['type' => 'wait', 'milliseconds' => 1000],
                ['type' => 'scrape'],
            ],
        ]);

        return $response->content;
    }

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'www.ebay.com' || Str::contains($host, 'ebay.com');
    }

    public function getThrottlingConfig(): array
    {
        return [
            'key' => FireCrawl::CACHE_KEY,
            'limit' => config('services.firecrawl.rate_limit_attempts'),
            'service' => 'firecrawl',
        ];
    }

    private function getFromFallback(DOMXPath $xpath): array
    {
        $sources = [];

        $fallback = $xpath->query("//div[contains(@class, 'ux-image-carousel-item') and contains(@class, 'image-treatment') and contains(@class, 'image')]//img");

        foreach ($fallback as $image) {
            $source = $image->getAttribute('data-src') ?? $this->parseSrcset($image->getAttribute('data-srcset'));

            if (filled($source)) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    private function parseSrcset(string $srcset): string
    {
        $sources = explode(',', $srcset);

        $source = $sources[count($sources) - 1] ?? '';

        return Str::before($source, ' ');
    }
}
