<?php

namespace App\Syllaby\Publisher\Metadata\Prompts;

use Illuminate\Support\Str;

class TagPrompt
{
    /**
     * Generate the tags prompt.
     */
    public static function generate(string $context, ?string $provider = null): string
    {
        $prompt = match (Str::lower($provider)) {
            'youtube' => self::youtube(),
            default => self::default(),
        };

        return str_replace(
            array_keys(self::bindings($context)),
            array_values(self::bindings($context)),
            $prompt
        );
    }

    /**
     * Bindings for the tags prompt.
     */
    private static function bindings(string $context): array
    {
        return [
            ':CONTEXT' => $context,
        ];
    }

    /**
     * Youtube specific tags prompt.
     */
    private static function youtube(): string
    {
        return <<<'PROMPT'
            Provide a comma-separated list of relevant tags for a YouTube video based on the context: :CONTEXT.
            Take the following example (web, coding, programming, development). Do not provide any explanation or subtext. 
            Just provide the tags.
        PROMPT;
    }

    /**
     * Default tags prompt.
     */
    private static function default(): string
    {
        return <<<'PROMPT'
            Provide a comma-separated list of relevant tags for a social media video based on the context: :CONTEXT.
            Take the following example (web, coding, programming, development). Do not provide any explanation or subtext. 
            Just provide the tags.
        PROMPT;
    }
}
