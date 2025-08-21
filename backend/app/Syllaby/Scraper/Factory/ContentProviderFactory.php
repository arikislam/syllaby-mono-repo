<?php

namespace App\Syllaby\Scraper\Factory;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Scraper\Providers\Har;
use App\Syllaby\Scraper\Providers\Ebay;
use App\Syllaby\Scraper\Providers\Amazon;
use App\Syllaby\Scraper\Providers\Redfin;
use App\Syllaby\Scraper\Providers\Zillow;
use App\Syllaby\Scraper\Providers\Fallback;
use App\Syllaby\Scraper\Contracts\ContentProvider;

class ContentProviderFactory
{
    /**
     * Cache of resolved providers.
     */
    protected array $resolved = [];

    /**
     * Map of supported domains to their fetcher implementations.
     */
    protected array $providers = [
        'www.amazon.com' => Amazon::class,
        'www.redfin.com' => Redfin::class,
        'www.zillow.com' => Zillow::class,
        'www.ebay.com' => Ebay::class,
        'www.har.com' => Har::class,
    ];

    /**
     * Get the appropriate fetcher for the URL.
     */
    public function make(string $url): ContentProvider
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        if (isset($this->resolved[$host])) {
            return $this->resolved[$host];
        }

        $provider = Arr::first($this->providers, function ($provider, $domain) use ($host) {
            return $host === $domain || Str::contains($host, $domain);
        }, Fallback::class);

        return tap(app($provider), function ($provider) use ($host) {
            $this->resolved[$host] = $provider;
        });
    }
}
