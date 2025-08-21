<?php

namespace App\Syllaby\Generators\Vendors\Assistants;

use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\DTOs\ChatResponse;
use Illuminate\Http\Client\ConnectionException;
use App\Syllaby\Generators\Contracts\ChatContract;
use App\Syllaby\Generators\Exceptions\UnavailableAiAssistantDriver;

class Claude implements ChatContract
{
    /**
     * Sends the given message to Claude.
     *
     * @throws ConnectionException
     */
    public function send($message, ?ChatConfig $config = null): ChatResponse
    {
        $config = ChatConfig::forClaude($config);

        $defaults = [
            'model' => $config->model,
            'max_tokens' => $config->maxCompletionTokens,
        ];

        $payload = array_merge($defaults, [
            'messages' => [['role' => 'user', 'content' => $message]],
        ]);

        try {
            $response = $this->http()->throw()->post('/messages', $payload);
        } catch (Throwable) {
            throw UnavailableAiAssistantDriver::fromProvider('Claude');
        }

        if ($response->serverError()) {
            throw UnavailableAiAssistantDriver::fromProvider('Claude');
        }

        if ($response->clientError()) {
            return new ChatResponse(text: null, completionTokens: 0);
        }

        return new ChatResponse(
            text: Str::trim(Arr::get($response, 'content.0.text', ''), '"'),
            completionTokens: Arr::get($response, 'usage.input_tokens', 0)
        );
    }

    /**
     * Configure Claude HTTP client.
     */
    private function http(): PendingRequest
    {
        return Http::asJson()->timeout(300)->retry(5, 500)
            ->baseUrl(config('services.claude.url'))
            ->withHeaders([
                'anthropic-version' => '2023-06-01',
                'x-api-key' => config('services.claude.key'),
            ]);
    }
}
