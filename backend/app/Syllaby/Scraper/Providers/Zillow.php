<?php

namespace App\Syllaby\Scraper\Providers;

use DOMXPath;
use Exception;
use DOMDocument;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;
use App\Syllaby\Scraper\Vendors\FireCrawl;
use App\Syllaby\Scraper\Contracts\Throttleable;
use App\Syllaby\Scraper\Contracts\ContentProvider;
use App\Syllaby\Scraper\Contracts\ScraperContract;

class Zillow implements ContentProvider, Throttleable
{
    protected string $type;

    protected const string HOME = 'home';

    protected const string APARTMENT = 'apartments';

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

        $items = match ($this->type) {
            self::HOME => $xpath->query('//ul[contains(@class, "StyledVerticalMediaWall-fshdp-8-106-0__sc-1liu0fm-3")]//source[@type="image/jpeg"]/@srcset'),
            self::APARTMENT => $xpath->query('//ul[contains(@class, "StyledVerticalMediaWall-sc-1est6c6-3")]//source[@type="image/jpeg"]/@srcset'),
        };

        $images = [];
        foreach ($items as $item) {
            $images[] = Str::of($item->nodeValue)->explode(',')
                ->map(fn ($item) => Str::before(trim($item), ' '))
                ->last();
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

        return $host === 'www.zillow.com' || Str::contains($host, 'zillow.com');
    }

    /**
     * Get raw HTML content from cache or scrape the URL
     */
    private function getRawContent(string $url): string
    {
        $this->type = $this->resolveType($url);

        return match ($this->type) {
            self::HOME => $this->scrapeHome($url),
            self::APARTMENT => $this->scrapeApartment($url),
            default => throw new Exception('Unknown Zillow property type'),
        };
    }

    /**
     * Scrape a home page.
     */
    private function scrapeHome(string $url): string
    {
        return $this->scraper->scrape($url, 'html', [
            'actions' => [
                ['type' => 'wait', 'milliseconds' => 2000],
                ['type' => 'click', 'selector' => 'button.StyledGallerySeeAllPhotosButton-fshdp-8-106-0__sc-167rdz3-0'],
                ['type' => 'wait', 'milliseconds' => 2200],
                ['type' => 'scrape'],
            ],
        ])->content;
    }

    /**
     * Scrape an apartment page.
     */
    private function scrapeApartment(string $url): string
    {
        return $this->scraper->scrape($url, 'html', [
            'actions' => [
                ['type' => 'wait', 'milliseconds' => 2000],
                ['type' => 'click', 'selector' => 'button.StyledGallerySeeAllPhotosButton-coeq76-0'],
                ['type' => 'wait', 'milliseconds' => 2200],
                ['type' => 'scrape'],
            ],
        ])->content;
    }

    /**
     * Resolve the type of the property.
     *
     * @throws Exception
     */
    private function resolveType(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = Str::of($path)->trim('/')->explode('/')->filter()->values();

        return match ($segments->first()) {
            'homedetails' => self::HOME,
            'apartments' => self::APARTMENT,
            default => throw new Exception('Unknown Zillow property type'),
        };
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
