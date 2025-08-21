<?php

namespace App\Syllaby\Ideas\Services\Networks;

use Log;
use Exception;
use App\Syllaby\Ideas\Idea;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\Enums\Networks;
use Illuminate\Support\Facades\Config;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\Prompts\KeywordPrompt;
use App\Syllaby\Generators\Vendors\OpenAIService;
use App\Syllaby\Generators\Vendors\Assistants\Chat;

class OpenAiNetwork extends NetworkSearcher
{
    public function __construct(protected OpenAIService $openai) {}

    /**
     * Attempts to generate ideas for the given keyword from OpenAI as a fallback strategy.
     */
    public function handle(Keyword $keyword): void
    {
        $type = Networks::type($keyword->network->value);

        $this->getKeywordSuggestions($keyword, $type);
    }

    /**
     * Searches for keyword suggestions with OpenAi.
     */
    protected function getKeywordSuggestions(Keyword $keyword, string $type): void
    {
        $chat = Chat::driver('gpt');

        /** Drivers can be switched at runtime in case of failure, therefore config should be dynamic */
        $config = match (Chat::getFacadeRoot()->getCurrentDriver()) {
            'gpt' => new ChatConfig(responseFormat: config('openai.json_schemas.keyword-suggestions')),
            default => null,
        };

        $response = $chat->send(KeywordPrompt::build($keyword->name, $type), $config);

        if (! $response->text || ! json_validate($response->text)) {
            throw new Exception('Unable to generate keyword suggestions');
        }

        $suggestions = json_decode($response->text, true)['output'] ?? [];

        if (! is_array($suggestions)) {
            return;
        }

        Idea::upsert(
            values: $this->parse($suggestions, $keyword, $type),
            uniqueBy: ['keyword_id', 'slug'],
            update: ['trend', 'type', 'trends', 'volume', 'cpc', 'competition', 'competition_label', 'valid_until']
        );
    }

    /**
     * Maps the api volume response to the database structure.
     */
    private function parse(array $results, Keyword $keyword, string $type): array
    {
        return Arr::map($results, fn ($result) => [
            'type' => $type,
            'locale' => 'en',
            'currency' => 'USD',
            'country' => 'USA',
            'keyword_id' => $keyword->id,
            'valid_until' => now()->addWeeks(3),
            'title' => Arr::get($result, 'question'),
            'cpc' => Arr::get($result, 'cpc', 0) ?? 0,
            'trend' => Arr::get($result, 'trend', 0) ?? 0,
            'trends' => Arr::get($result, 'trend_line', 0) ?? 0,
            'slug' => Str::slug(Arr::get($result, 'question')),
            'volume' => Arr::get($result, 'search_volume', 0) ?? 0,
            'competition' => $this->competitionValue(Arr::get($result, 'competition', 0) ?? 0),
            'competition_label' => $this->competitionLabel(Arr::get($result, 'competition', 0) ?? 0),
        ]);
    }
}
