<?php

namespace App\Syllaby\Generators\Vendors\Images;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Sleep;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Credits\CreditHistory;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use Illuminate\Http\Client\ConnectionException;
use App\Syllaby\Generators\Contracts\ImageGenerator;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;

class Replicate implements ImageGenerator
{
    private const int SLEEP_TIME = 1;

    private const int POLLING_MAX_ATTEMPTS = 90;

    private const int PREDICTION_MAX_ATTEMPTS = 4;

    private bool $async = false;

    /**
     * Triggers an AI image generation.
     *
     * @throws ConnectionException|Exception
     */
    public function image(array $options, ?string $prompt = null): ?ImageGeneratorResponse
    {
        $response = $this->prediction($options);

        if (! $this->async) {
            $response = $this->polling($response->json('id'), $options);
        }

        if (blank($response)) {
            return null;
        }

        return ImageGeneratorResponse::fromReplicate($response->json(), $prompt);
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
     * Polling for the prediction.
     */
    private function polling(string $id, array $options, int $attempts = self::POLLING_MAX_ATTEMPTS): ?Response
    {
        if ($attempts <= 0) {
            return null;
        }

        Sleep::for(self::SLEEP_TIME)->seconds();

        $response = $this->http()->throw()->get("/predictions/{$id}");

        if ($response->failed()) {
            Log::error('Replicate polling failed', [
                'id' => $id,
                'options' => $options,
                'attempts' => $attempts,
                'response' => $response->body(),
                'headers' => $response->headers(),
            ]);
        }

        if ($this->inProgress($response->json('status'))) {
            return $this->polling($id, $options, $attempts - 1);
        }

        if (blank($response) || $response->json('status') !== 'succeeded') {
            return null;
        }

        return $response;
    }

    /**
     * Trigger a prediction.
     */
    private function prediction(array $options, int $attempts = self::PREDICTION_MAX_ATTEMPTS): ?Response
    {
        if ($attempts <= 0) {
            return null;
        }

        [$owner, $name, $version] = $this->parse(Arr::get($options, 'model'));

        $response = match (true) {
            filled($version) => $this->version($version, $options),
            (filled($owner) && filled($name)) => $this->model($owner, $name, $options),
            default => throw new Exception('Invalid model reference'),
        };

        if ($this->shouldRetry($response)) {
            Arr::forget($options, 'input.seed');
            Sleep::for(self::SLEEP_TIME)->seconds();

            return $this->prediction($options, $attempts - 1);
        }

        return $response;
    }

    /**
     * Get the number of credits required for the given duration.
     */
    public function credits($duration): int
    {
        if ($duration <= 60) {
            return config('credit-engine.images.replicate');
        }

        return image_render_credits('replicate', $duration);
    }

    /**
     * Charge the user for the given image generation.
     */
    public function charge(int $credits, Faceless $faceless, User $user): void
    {
        (new CreditService($user))->decrement(
            type: CreditEventEnum::BULK_AI_IMAGES_GENERATED,
            creditable: $faceless,
            amount: $credits,
            label: Str::limit($faceless->script, CreditHistory::TRUNCATED_LENGTH)
        );
    }

    /**
     * Get the provider.
     */
    public function provider(): AssetProvider
    {
        return AssetProvider::REPLICATE;
    }

    /**
     * Run the inference for a version.
     */
    protected function version(string $version, array $options): Response
    {
        return $this->http()->post('/predictions', [
            'version' => $version, ...$this->payload($options),
        ]);
    }

    /**
     * Run the inference for a model.
     */
    protected function model(string $owner, string $name, array $options): Response
    {

        return $this->http()->post("/models/{$owner}/{$name}/predictions", $this->payload($options));
    }

    /**
     * Get the payload for the inference.
     */
    protected function payload(array $options): array
    {
        $payload = [
            'input' => array_merge(Arr::get($options, 'input'), ['disable_safety_checker' => true]),
        ];

        if ($this->async) {
            $query = Arr::query(Arr::only($options, 'context'));
            $payload['webhook_events_filter'] = config('services.replicate.webhook.filters');
            $payload['webhook'] = sprintf('%s?%s', config('services.replicate.webhook.url'), $query);
        }

        return $payload;
    }

    /**
     * Checks whether the given status is not yet a finished one.
     */
    private function inProgress(string $status): bool
    {
        return in_array($status, ['starting', 'processing']);
    }

    /**
     * Checks whether the given response should be retried.
     */
    private function shouldRetry(Response $response): bool
    {
        return $response->failed() || $response->json('status') === 'failed';
    }

    /**
     * Parse a model reference.
     */
    private function parse(string $model): ?array
    {
        if (! preg_match('/^(?<owner>[^\/]+)\/(?<name>[^\/\:]+)(?:\:(?<version>.+))?$/', $model, $matches)) {
            return null;
        }

        return [Arr::get($matches, 'owner'), Arr::get($matches, 'name'), Arr::get($matches, 'version')];
    }

    /**
     * Configure Replicate HTTP client.
     */
    private function http(): PendingRequest
    {
        return Http::asJson()->timeout(140)
            ->withToken(config('services.replicate.key'))
            ->baseUrl(config('services.replicate.url'));
    }
}
