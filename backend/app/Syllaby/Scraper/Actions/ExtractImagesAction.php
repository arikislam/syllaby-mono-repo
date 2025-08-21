<?php

namespace App\Syllaby\Scraper\Actions;

use Cache;
use Exception;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\System\Traits\HandlesThrottling;
use App\Syllaby\Scraper\Vendors\FireCrawl;
use App\Syllaby\Assets\DTOs\AssetCreationData;
use App\Syllaby\Assets\Actions\CreateFacelessAssetAction;
use App\Syllaby\Scraper\Contracts\ContentProvider;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Scraper\Factory\ContentProviderFactory;

class ExtractImagesAction
{
    use HandlesThrottling;

    /**
     * Maximum number of retries.
     */
    private int $maxRetries = 4;

    /**
     * Delay between retries in milliseconds.
     */
    private int $retryDelay = 2000;

    public function __construct(
        protected CreateFacelessAssetAction $asset,
        protected ContentProviderFactory $factory,
        protected TransloadMediaAction $transload,
    ) {}

    public function handle(Faceless $faceless, array $input): Faceless
    {
        $provider = $this->factory->make($url = Arr::get($input, 'url'));

        $config = $provider->getThrottlingConfig();

        $this->ensureIsThrottled($config['key'], $config['limit'], $config['service']);

        $images = $this->extractImagesWithRetry($provider, $url);

        Log::alert("Extracted images from {$url}", ['images' => $images]);

        DB::transaction(function () use ($images, $faceless) {
            $this->pruneExistingAssets($faceless);
            $this->saveNewAssets($faceless, $images);
        });

        return tap($faceless)->update(['type' => Faceless::URL_BASED]);
    }

    private function findMimeType(mixed $url): string
    {
        return rescue(function () use ($url) {
            return Http::timeout(5)->head($url)->header('Content-Type');
        }, fn () => $this->guessMimeFromExtension($url));
    }

    private function guessMimeFromExtension(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }

    private function extractImagesWithRetry(ContentProvider $provider, string $url): array
    {
        return retry(times: $this->maxRetries, callback: function () use ($provider, $url) {
            $images = $provider->extractImages($url);

            if (empty($images)) {
                Cache::forget(sprintf('%s:%s', FireCrawl::CACHE_KEY, md5($url)));
                throw new Exception;
            }

            return $images;
        }, sleepMilliseconds: $this->retryDelay) ?? [];
    }

    private function saveNewAssets(Faceless $faceless, array $images): void
    {
        collect($images)->each(function ($url, $index) use ($faceless) {
            $data = AssetCreationData::forScrapedMedia($faceless, $this->findMimeType($url), $index, true);
            $asset = $this->asset->handle($faceless, $data);
            $this->transload->handle($asset, $url);
        });
    }

    private function pruneExistingAssets(Faceless $faceless): void
    {
        $existing = $faceless->assets()->get();

        $faceless->assets()->detach();

        $existing->each->delete();
    }
}
