<?php

namespace App\Syllaby\Publisher\Metrics\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\Social\TikTokProvider;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

abstract class AbstractHandler
{
    /**
     * The raw response from the social network.
     */
    protected mixed $response;

    /**
     * The formatted data from the social network.
     */
    protected Collection $data;

    /**
     * The array to be inserted in the database.
     */
    protected array $insertions;

    /**
     * Fetch the metrics data from the social network.
     */
    abstract protected function fetch(): self;

    /**
     * Transform the data to a common format.
     */
    abstract protected function transform(): self;

    /**
     * Validate the access token for the social account.
     *
     * @throws InvalidRefreshTokenException
     */
    abstract protected function validate(): self;

    /**
     * Format the data to be inserted in the database.
     */
    protected function prepare(): self
    {
        $keys = PublicationMetricKey::select(['id', 'slug'])->get();

        $this->insertions = $this->data->map(fn($item) => [
            'publication_id' => $this->publications->firstWhere('provider_media_id', $item['id'])->publication_id,
            'social_channel_id' => $this->publications->firstWhere('provider_media_id', $item['id'])->social_channel_id,
            'publication_metric_key_id' => $keys->firstWhere('slug', $item['slug'])->id,
            'value' => $item['value'],
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        return $this;
    }

    /**
     * Persist the data in the database.
     */
    protected function store(): void
    {
        attempt(fn() => PublicationMetricValue::upsert(
            $this->insertions,
            ['publication_id', 'social_channel_id', 'publication_metric_key_id', 'date'],
            ['value', 'updated_at']
        ));
    }

    /**
     * Write a log message.
     */
    protected function writeLog(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}