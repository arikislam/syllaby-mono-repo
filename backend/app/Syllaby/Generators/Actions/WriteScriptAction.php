<?php

namespace App\Syllaby\Generators\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Generators\DTOs\FacelessContext;
use App\Syllaby\Generators\Prompts\FacelessPrompt;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Generators\Prompts\FacelessScriptPrompt;
use App\Syllaby\Generators\Prompts\FacelessOutlinePrompt;

class WriteScriptAction
{
    /**
     * The number of topics to explain.
     */
    const int TOPICS_TO_EXPLAIN = 3;

    /**
     * Handle the action.
     */
    public function handle(Generator $generator, array $input = []): ?ChatResponse
    {
        $duration = Arr::get($input, 'duration', $generator->length);

        return match (true) {
            $duration > 300 => $this->generateForLongVideo($generator),
            default => $this->generateForShortVideo($generator, $duration),
        };
    }

    /**
     * Generate script for long videos.
     */
    private function generateForLongVideo(Generator $generator): ?ChatResponse
    {
        /** @var ChatResponse $outline */
        $outline = Chat::driver('gpt')->send(FacelessOutlinePrompt::build($generator));

        if (blank($outline->text)) {
            return null;
        }

        $topics = $this->parseTopics($outline);

        $context = new FacelessContext(
            topics: $topics,
            explained: min(count($topics), self::TOPICS_TO_EXPLAIN)
        );

        $generator->update(['context' => $context->toArray()]);

        $final = collect(range(0, static::TOPICS_TO_EXPLAIN))
            ->map(fn ($index) => Chat::driver('gpt')->send(FacelessScriptPrompt::build($generator, $topics[$index])))
            ->filter(fn (ChatResponse $response) => ! empty($response->text));

        if ($final->isEmpty()) {
            return null;
        }

        return new ChatResponse(
            text: $final->pluck('text')->join("\n"),
            completionTokens: $final->sum('completionTokens')
        );
    }

    /**
     * Generate script for short videos.
     */
    private function generateForShortVideo(Generator $generator, int $duration): ?ChatResponse
    {
        $response = Chat::driver('gpt')->send(FacelessPrompt::build($generator, $duration));

        if (blank($response->text)) {
            return null;
        }

        return $response;
    }

    /**
     * Parse the topics from the outline.
     */
    private function parseTopics(ChatResponse $outline): array
    {
        $topics = explode("\n", $outline->text);

        return Arr::map($topics, fn ($topic) => Str::of($topic)->trim()->toString());
    }
}
