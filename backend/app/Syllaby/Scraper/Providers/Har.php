<?php

namespace App\Syllaby\Scraper\Providers;

use DOMXPath;
use DOMDocument;
use Stevebauman\Purify\Facades\Purify;
use App\Syllaby\Scraper\Vendors\FireCrawl;
use App\Syllaby\Scraper\Contracts\Throttleable;
use App\Syllaby\Scraper\Contracts\ContentProvider;
use App\Syllaby\Scraper\Contracts\ScraperContract;

class Har implements ContentProvider, Throttleable
{
    public function __construct(protected ScraperContract $scraper) {}

    public function extractImages(string $url): array
    {
        $response = $this->scraper->scrape($url, 'html', [
            'actions' => [
                ['type' => 'wait', 'milliseconds' => 3000],
                ['type' => 'click', 'selector' => '[data-media-type="photo-Tab"]'],
                ['type' => 'wait', 'milliseconds' => 2000],
                ['type' => 'scrape'],
            ],
        ]);

        $dom = new DOMDocument;

        @$dom->loadHTML($response->content, LIBXML_NOERROR);

        $xpath = new DOMXPath($dom);

        $items = $xpath->query('//div[contains(@class, "all_photos_container")]//ul/li/img/@src');

        $images = [];

        foreach ($items as $item) {
            $src = $item->nodeValue;

            if (str_starts_with($src, 'http')) {
                $images[] = $this->stripQueryString($src);
            }
        }

        return array_filter($images);
    }

    public function extractContent(string $url): string
    {
        $response = $this->scraper->scrape($url, 'html', fresh: true);

        return Purify::config(['HTML.Allowed' => ''])->clean($response->content);
    }

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'www.har.com' || str_contains($host, 'har.com');
    }

    public function getThrottlingConfig(): array
    {
        return [
            'key' => FireCrawl::CACHE_KEY,
            'limit' => config('services.firecrawl.rate_limit_attempts'),
            'service' => 'firecrawl',
        ];
    }

    private function stripQueryString(string $src): string
    {
        $parts = parse_url($src);

        return sprintf('%s://%s%s', $parts['scheme'], $parts['host'], $parts['path']);
    }
}
