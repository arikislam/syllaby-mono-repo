<?php

namespace App\Syllaby\Generators\Actions;

use Arr;
use Str;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Generators\Prompts\UrlToScriptPrompt;

class ConvertRawContentToScriptAction
{
    public function __construct() {}

    public function handle(Generator $generator, array $input, string $content): ?string
    {
        $duration = Arr::get($input, 'duration', $generator->length);

        /** @var ChatResponse $response */
        $response = Chat::driver('gpt')->send(UrlToScriptPrompt::build($generator, $duration, $content));

        if (blank($response->text)) {
            return null;
        }

        (new CreditService)->decrement(
            type: CreditEventEnum::CONTENT_PROMPT_REQUESTED,
            creditable: $generator,
            amount: $response->completionTokens,
            label: Str::limit($response->text, CreditHistory::TRUNCATED_LENGTH)
        );

        return $response->text;
    }
}
