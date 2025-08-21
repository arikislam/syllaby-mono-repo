<?php

namespace App\Syllaby\Generators\Vendors\Assistants;

use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Generators\Contracts\ChatContract;
use App\Syllaby\Generators\Exceptions\UnavailableAiAssistantDriver;

class Gemini implements ChatContract
{
    /**
     * Sends the given message to Gemini.
     */
    public function send(string $message, ?ChatConfig $config = null): ChatResponse
    {
        $config = ChatConfig::forGemini($config);

        try {
            $response = $this->http()->post("/models/{$config->model}:generateContent", $this->payload($message, $config));
        } catch (Throwable) {
            throw UnavailableAiAssistantDriver::fromProvider('Gemini');
        }

        if ($response->serverError()) {
            throw UnavailableAiAssistantDriver::fromProvider('Gemini');
        }

        if ($response->clientError()) {
            return new ChatResponse(text: null, completionTokens: 0);
        }

        return new ChatResponse(
            text: $response->json('candidates.0.content.parts.0.text', ''),
            completionTokens: $response->json('usageMetadata.totalTokenCount', 0),
        );
    }

    /**
     * Builds the payload for the Gemini API.
     */
    private function payload(string $message, ChatConfig $config): array
    {
        return [
            'contents' => [
                'parts' => ['text' => $message],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ],
        ];
    }

    /**
     * Gemini Http client setup.
     */
    private function http(): PendingRequest
    {
        return Http::asJson()->timeout(300)->retry(5, 500)
            ->baseUrl(config('services.gemini.url'))
            ->withQueryParameters([
                'key' => config('services.gemini.key'),
            ]);
    }
}
