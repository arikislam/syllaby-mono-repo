<?php

namespace App\Syllaby\Generators\Vendors\Assistants;

use OpenAI;
use Throwable;
use OpenAI\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\Client as Http;
use App\Syllaby\Loggers\Jobs\OpenAILogJob;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Generators\Contracts\ChatContract;
use App\Syllaby\Generators\Exceptions\UnavailableAiAssistantDriver;

class GPT implements ChatContract
{
    /**
     * Sends the given message to GhatGPT.
     *
     * @throws UnavailableAiAssistantDriver|Throwable
     */
    public function send(string $message, ?ChatConfig $config = null): ChatResponse
    {
        $config = ChatConfig::forGPT($config);

        $defaults = [
            'model' => $config->model,
            'max_completion_tokens' => $config->maxCompletionTokens,
            'top_p' => $config->topP,
            'presence_penalty' => $config->presencePenalty,
            'frequency_penalty' => $config->frequencyPenalty,
            'response_format' => $config->responseFormat,
        ];

        $payload = array_merge($defaults, [
            'messages' => [['role' => 'user', 'content' => $message]],
        ]);

        try {
            $response = retry(5, fn () => $this->client()->chat()->create($payload)->toArray(), 500);
        } catch (Throwable) {
            throw UnavailableAiAssistantDriver::fromProvider('GPT');
        }

        if (blank($response)) {
            return new ChatResponse(text: null, completionTokens: 0);
        }

        dispatch(new OpenAILogJob($payload, $response));

        return new ChatResponse(
            text: Str::trim(Arr::get($response, 'choices.0.message.content', ''), '"'),
            completionTokens: Arr::get($response, 'usage.completion_tokens', 0),
        );
    }

    /**
     * GPT Http client setup.
     */
    private function client(): Client
    {
        return OpenAI::factory()
            ->withApiKey(config('openai.token'))
            ->withBaseUri(config('openai.base_url'))
            ->withHttpClient(new Http(['timeout' => 300]))
            ->make();
    }
}
