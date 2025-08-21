<?php

namespace App\Syllaby\Generators\Vendors\Assistants;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Generators\Contracts\ChatContract;

class Xai implements ChatContract
{
    /**
     * Sends the given message to GhatGPT.
     */
    public function send(string $message, ?ChatConfig $config = null): ChatResponse
    {
        $config = ChatConfig::forXAi($config);

        // return new ChatResponse(
        //     text: Str::trim(Arr::get($response, 'choices.0.message.content', ''), '"'),
        //     completionTokens: Arr::get($response, 'usage.completion_tokens', 0),
        // );
    }

    /**
     * GPT Http client setup.
     */
    private function http(): PendingRequest
    {
        return Http::asJson()->timeout(300)->retry(5, 500)
            ->baseUrl(config('services.xai.url'));
    }
}
