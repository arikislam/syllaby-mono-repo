<?php

namespace Tests\Stubs;

use Tests\TestCase;
use Illuminate\Support\Manager;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Generators\Contracts\ChatContract;

class StubChatManager extends Manager
{
    public function createGptDriver(): ChatContract
    {
        return new class implements ChatContract
        {
            public function send(string $message, ?ChatConfig $config = null): ChatResponse
            {
                $response = $config === null ? TestCase::OPEN_AI_MOCKED_RESPONSE : sprintf('{"data": {"text": "%s"}}', TestCase::OPEN_AI_MOCKED_RESPONSE);

                return new ChatResponse($response, TestCase::OPEN_AI_MOCKED_USED_TOKENS);
            }
        };
    }

    public function createClaudeDriver(): ChatContract
    {
        return new class implements ChatContract
        {
            public function send(string $message, ?ChatConfig $config = null): ChatResponse
            {
                return new ChatResponse(TestCase::OPEN_AI_MOCKED_RESPONSE, TestCase::OPEN_AI_MOCKED_USED_TOKENS);
            }
        };
    }

    public function getCurrentDriver(): string
    {
        return 'gpt';
    }

    public function getDefaultDriver()
    {
        return config('services.assistant.default');
    }
}
