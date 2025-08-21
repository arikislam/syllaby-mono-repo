<?php

namespace App\Syllaby\Videos\Vendors\Moderators;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Videos\DTOs\ModeratorData;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Videos\Contracts\ImageModerator;
use App\Syllaby\Generators\Prompts\ImageModerationPrompt;

class Gemini implements ImageModerator
{
    public function inspect(string $url): ModeratorData
    {
        try {
            $image = Http::get($url);
            $mime = $image->header('Content-Type') ?? 'image/jpeg';

            $response = $this->http()->post(sprintf('/models/%s:generateContent', config('services.gemini.model')), [
                'contents' => [[
                    'parts' => [
                        ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode($image->body())]],
                        ['text' => ImageModerationPrompt::build()],
                    ],
                ]],
            ]);

            return ModeratorData::fromGemini($response->json());
        } catch (Exception $exception) {
            Log::error('Gemini moderation failed:', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            // If Gemini API is down, we don't want to block the user.
            // We would give user the benefit of the doubt.
            return new ModeratorData(flagged: false);
        }
    }

    /**
     * Configure Gemini HTTP client.
     */
    private function http(): PendingRequest
    {
        return Http::asJson()->retry(3, 1000)
            ->withHeader('x-goog-api-key', config('services.gemini.key'))
            ->baseUrl(config('services.gemini.url'));
    }
}
