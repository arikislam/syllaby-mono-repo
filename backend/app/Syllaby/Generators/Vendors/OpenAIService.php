<?php

namespace App\Syllaby\Generators\Vendors;

use OpenAI;
use OpenAI\Client;
use GuzzleHttp\Client as GuzzleClient;
use App\Syllaby\Loggers\Jobs\OpenAILogJob;

class OpenAIService
{
    public function __construct(protected string $model = 'gpt-4o-2024-08-06') {}

    public function completions(string $prompt, array $messages = []): array
    {
        $response = $this->client()->chat()->create($this->bodyParams($prompt, $messages));

        if (! blank($response)) {
            OpenAILogJob::dispatch($this->bodyParams($prompt), $response->toArray())->onQueue('default');
        }

        return [
            'used_tokens' => data_get($response, 'usage.completion_tokens', 0),
            'text' => trim(data_get($response, 'choices.0.message.content', ''), '"'),
        ];
    }

    private function bodyParams(string $prompt, array $messages = []): array
    {
        return [
            'messages' => array_merge($messages, [
                ['role' => 'user', 'content' => $prompt],
            ]),
            'model' => $this->model,
            'max_tokens' => config('openai.max_token'),
            'temperature' => config('openai.temperature'),
            'top_p' => config('openai.top_p'),
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ];
    }

    private function client(): Client
    {
        return OpenAI::factory()
            ->withApiKey(config('openai.token'))
            ->withBaseUri(config('openai.base_url'))
            ->withHttpClient(new GuzzleClient(['timeout' => 300]))
            ->make();
    }
}
