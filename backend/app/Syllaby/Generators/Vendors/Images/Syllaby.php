<?php

namespace App\Syllaby\Generators\Vendors\Images;

use Illuminate\Support\Arr;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Syllaby\Generators\Contracts\ImageGenerator;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;

class Syllaby implements ImageGenerator
{
    private const int SLEEP_TIME = 1;

    private const int POLLING_MAX_ATTEMPTS = 90;

    private const int PREDICTION_MAX_ATTEMPTS = 4;

    protected bool $async = false;

    /**
     * Triggers an AI image generation.
     *
     * @throws ConnectionException|Exception
     */
    public function image(array $options, ?string $prompt = null): ?ImageGeneratorResponse
    {
        $response = $this->prediction($options);

        if (! $this->async) {
            $response = $this->polling($response->json('id'));
        }

        if (blank($response)) {
            return null;
        }

        return ImageGeneratorResponse::fromSyllaby($response->json(), $prompt);
    }

    /**
     * Set the async mode.
     */
    public function async(): self
    {
        $this->async = true;

        return $this;
    }

    /**
     * Get the provider.
     */
    public function provider(): AssetProvider
    {
        return AssetProvider::SYLLABY;
    }

    /**
     * Trigger a prediction.
     */
    private function prediction(array $options, int $attempts = self::PREDICTION_MAX_ATTEMPTS): ?Response
    {
        if ($attempts <= 0) {
            return null;
        }

        $response = $this->http()->post('/image-generation/finetuned/lora/flux-1/', $this->payload($options));

        if ($response->failed()) {
            return tap(null, fn () => Log::warning('Failed to trigger the image generation', [
                'options' => $options,
                'response' => $response->body(),
            ]));
        }

        if ($this->shouldRetry($response)) {
            Sleep::for(self::SLEEP_TIME)->seconds();

            return $this->prediction($options, $attempts - 1);
        }

        return $response;
    }

    /**
     * Polling for the prediction.
     */
    private function polling(string $id, int $attempts = self::POLLING_MAX_ATTEMPTS): ?Response
    {
        if ($attempts <= 0) {
            return null;
        }

        Sleep::for(self::SLEEP_TIME)->seconds();

        $response = $this->http()->throw()->get("/image-generation/finetuned/lora/flux-1/{$id}/");

        if ($response->failed()) {
            return tap(null, fn () => Log::warning('Failed to get the status of the image generation', [
                'id' => $id,
                'response' => $response->body(),
            ]));
        }

        if ($this->inProgress($response->json('status'))) {
            return $this->polling($id, $attempts - 1);
        }

        if (blank($response) || $response->json('status') !== 'succeeded') {
            return null;
        }

        return $response;
    }

    /**
     * Get the payload for the inference.
     */
    private function payload(array $options): array
    {
        $payload = [
            'prompt' => Arr::get($options, 'prompt'),
            'model_id' => Arr::get($options, 'model'),
            'num_outputs' => Arr::get($options, 'num_outputs', 1),
            'aspect_ratio' => Arr::get($options, 'aspect_ratio', 'custom'),
            'width' => Arr::get($options, 'width', 720),
            'height' => Arr::get($options, 'height', 1024),
            'output_format' => Arr::get($options, 'output_format', 'jpg'),
        ];

        if ($this->async) {
            $query = Arr::query(Arr::only($options, 'context'));
            $payload['client_webhook_url'] = sprintf('%s?%s', config('services.character_consistency.webhook.url'), $query);
        }

        return $payload;
    }

    /**
     * Checks whether the given status is not yet a finished one.
     */
    private function inProgress(string $status): bool
    {
        return in_array($status, ['queued', 'starting', 'processing']);
    }

    /**
     * Checks whether the given response should be retried.
     */
    private function shouldRetry(Response $response): bool
    {
        return $response->failed() || $response->json('status') === 'failed';
    }

    /**
     * Get the provider.
     */
    public function http(): PendingRequest
    {
        return Http::asJson()->baseUrl(
            config('services.character_consistency.url')
        );
    }
}
