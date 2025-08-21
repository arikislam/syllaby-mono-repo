<?php

namespace App\Syllaby\Videos\Vendors\Moderators;

use Exception;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Videos\DTOs\ModeratorData;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Videos\Contracts\ImageModerator;

class Replicate implements ImageModerator
{
    const int MAX_RETRIES = 5;

    public function inspect(string $url): ModeratorData
    {
        try {
            $response = $this->client()->post('/predictions', [
                'version' => '88c3624a13d60bb5ecd0cb215e49e39d2a2135c211bcb94fc801d3def46803c4',
                'input' => ['image' => $url],
            ]);

            $id = $response->json('id') ?? throw new Exception('No id returned from Replicate');

            $attempts = 0;

            $response = $this->client()->get("/predictions/{$id}");

            while ($this->inProgress($response->json('status')) && $attempts < self::MAX_RETRIES) {
                Sleep::for(1)->seconds();
                $response = $this->client()->get("/predictions/{$id}");
                $attempts++;
            }

            if (blank($status = $response->json('status')) || $status !== 'succeeded') {
                throw new Exception;
            }
        } catch (Exception $exception) {
            Log::alert("Replicate moderation failed: {$exception->getMessage()}");

            // If Moderation API is down, we don't want to block the user.
            // We would give user the benefit of the doubt.
            return new ModeratorData(flagged: false);
        }

        return ModeratorData::fromReplicate($response->json());
    }

    private function client(): PendingRequest
    {
        return Http::timeout(140)
            ->withToken(config('services.replicate.key'))
            ->baseUrl(config('services.replicate.url'))
            ->retry(3, 1000)
            ->asJson();
    }

    private function inProgress(string $status): bool
    {
        return in_array($status, ['starting', 'processing']);
    }
}
