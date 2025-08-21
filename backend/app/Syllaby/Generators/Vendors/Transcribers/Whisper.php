<?php

namespace App\Syllaby\Generators\Vendors\Transcribers;

use Illuminate\Support\Arr;
use Illuminate\Support\Sleep;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;
use App\Syllaby\Generators\DTOs\CaptionResponse;
use App\Syllaby\Generators\Contracts\TranscriberContract;

class Whisper implements TranscriberContract
{
    private const float SLEEP_TIME = 1.5;

    private const int MAX_ATTEMPTS = 90;

    /**
     * Create closed captions from the audio url.
     *
     * @throws ConnectionException
     */
    public function run(string $url, array $options = []): ?CaptionResponse
    {
        $response = $this->http()->post('/predictions', [
            'version' => '3ab86df6c8f54c11309d4d1f930ac292bad43ace52d10c80d87eb258b3c9f79c',
            'input' => [
                'audio' => $url,
                'batch_size' => 7,
                'timestamp' => 'word',
                'task' => 'transcribe',
                'language' => $this->language(Arr::get($options, 'language')),
            ],
        ]);

        if ($response->failed()) {
            return null;
        }

        $transcription = $this->poll($response->json('id'));

        if ($transcription->json('status') !== 'succeeded') {
            return null;
        }

        return CaptionResponse::fromWhisper($transcription->json('output'));
    }

    /**
     * Calculate the number of credits for the given duration.
     */
    public function credits(float $duration): int
    {
        $unit = config('credit-engine.transcription.whisper');

        return ceil($duration / 60) * $unit;
    }

    /**
     * Keeps checking for the image generation to be finished (completed or failed).
     *
     * @throws ConnectionException
     */
    private function poll(string $id, int $attempts = self::MAX_ATTEMPTS): ?Response
    {
        if ($attempts <= 0) {
            return null;
        }

        Sleep::for(self::SLEEP_TIME)->seconds();

        $response = $this->http()->throw()->get("/predictions/{$id}");

        if ($this->inProgress($response->json('status'))) {
            return $this->poll($id, $attempts - 1);
        }

        return $response;
    }

    /**
     * Checks whether the given status is not yet a finished one.
     */
    private function inProgress(string $status): bool
    {
        return in_array($status, ['starting', 'processing']);
    }

    /**
     * Maps the language code to a recognizable value.
     */
    private function language(?string $language = null): string
    {
        return match ($language) {
            'en' => 'english',
            'fe' => 'french',
            'de' => 'german',
            'it' => 'italian',
            'es' => 'spanish',
            default => 'None'
        };
    }

    /**
     * Configure Replicate HTTP client.
     */
    private function http(): PendingRequest
    {
        return Http::asJson()->timeout(150)
            ->withToken(config('services.replicate.key'))
            ->baseUrl(config('services.replicate.url'));
    }
}
