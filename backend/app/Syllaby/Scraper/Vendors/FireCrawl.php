<?php

namespace App\Syllaby\Scraper\Vendors;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Scraper\Jobs\LogScraperResponse;
use App\Syllaby\Scraper\DTOs\ScraperResponseData;
use App\Syllaby\Scraper\Contracts\ScraperContract;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidUrl;
use Spatie\MediaLibrary\MediaCollections\Exceptions\UnreachableUrl;

/** @see https://docs.firecrawl.dev/api-reference/endpoint/scrape */
class FireCrawl implements ScraperContract
{
    const string CACHE_KEY = 'firecrawl-attempts';

    public function __construct(protected ?User $user = null)
    {
        $this->user ??= Auth::user();
    }

    public function scrape(string $url, string $format = 'rawHtml', array $options = [], bool $fresh = false): ScraperResponseData
    {
        if (Cache::missing($this->getCacheKey($url)) || $fresh) {
            $response = $this->sendRequest($url, $format, $options);

            if (blank($response->json())) {
                Log::critical('FireCrawl - Received Empty Response', ['url' => $url, 'response' => $response->body()]);
                throw new UnreachableUrl(__('scraper.unreachable_url'));
            }

            dispatch(new LogScraperResponse($response->json(), $url, $this->user, $format));

            if ($response->serverError()) {
                Log::alert('FireCrawl server error', ['response' => $response->json()]);
                throw new InvalidUrl(__('scraper.invalid_url'));
            }

            if ($response->clientError()) {
                Log::alert('FireCrawl client error', ['response' => $response->json()]);
                throw new UnreachableUrl(__('scraper.unreachable_url'));
            }

            Cache::put($this->getCacheKey($url), $response->json(), now()->addHour());

            return ScraperResponseData::fromResponse($response->json(), $format);
        }

        $response = Cache::pull($this->getCacheKey($url));

        return ScraperResponseData::fromResponse($response, $format);
    }

    private function sendRequest(string $url, string $format, array $options): Response
    {
        $response = $this->http()->post('/scrape', array_merge([
            'url' => Str::lower($url),
            'proxy' => 'stealth',
            'formats' => Arr::wrap($format),
        ], $options));

        if ($response->successful()) {
            Cache::put($this->getCacheKey($url), $response, now()->addHour());
        }

        return $response;
    }

    private function http(): PendingRequest
    {
        return Http::asJson()
            ->timeout(300)
            ->retry(5, 1000)
            ->withToken(config('services.firecrawl.key'))
            ->baseUrl('https://api.firecrawl.dev/v1');
    }

    private function getCacheKey(string $url): string
    {
        return sprintf('%s:%s', static::CACHE_KEY, md5($url));
    }
}
