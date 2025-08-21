<?php

namespace App\Syllaby\Videos\Vendors\Moderators;

use Exception;
use OpenAI\Client;
use OpenAI as OpenAiClient;
use GuzzleHttp\Client as Http;
use Illuminate\Support\Facades\Log;
use App\System\Traits\HandlesThrottling;
use App\Syllaby\Videos\DTOs\ModeratorData;
use App\Syllaby\Videos\Contracts\ImageModerator;

class OpenAI implements ImageModerator
{
    use HandlesThrottling;

    const string MODERATION_THROTTLE_KEY = 'moderation-attempt:';

    public function inspect(string $url): ModeratorData
    {
        try {
            $this->ensureIsThrottled(static::MODERATION_THROTTLE_KEY, config('openai.moderation.rate_limit'), 'OpenAI Moderation');

            $response = retry(5, fn () => $this->client()->moderations()->create([
                'model' => 'omni-moderation-latest',
                'input' => [
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => $url],
                    ],
                ],
            ]), 2000);

            return ModeratorData::fromOpenAi($response->toArray());
        } catch (Exception $exception) {
            Log::alert('OpenAi moderation failed:', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            // If Moderation API is down, we don't want to block the user.
            // We would give user the benefit of the doubt.
            return new ModeratorData(flagged: false);
        }
    }

    private function client(): Client
    {
        return OpenAiClient::factory()
            ->withApiKey(config('openai.token'))
            ->withBaseUri(config('openai.base_url'))
            ->withHttpClient(new Http(['timeout' => 300]))
            ->make();
    }
}
