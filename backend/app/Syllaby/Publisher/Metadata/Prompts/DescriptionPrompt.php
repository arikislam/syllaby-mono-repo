<?php

namespace App\Syllaby\Publisher\Metadata\Prompts;

use Illuminate\Support\Str;

class DescriptionPrompt
{
    /**
     * Generate the description prompt.
     */
    public static function generate(string $context, ?string $provider = null): string
    {
        $prompt = match (Str::lower($provider)) {
            'youtube' => self::youtube(),
            'tiktok' => self::tiktok(),
            'linkedin' => self::linkedin(),
            'facebook' => self::facebook(),
            'threads' => self::threads(),
            'instagram' => self::instagram(),
            default => self::default(),
        };

        return str_replace(
            array_keys(self::bindings($context)),
            array_values(self::bindings($context)),
            $prompt
        );
    }

    /**
     * Bindings for the description prompt.
     */
    public static function bindings(string $context): array
    {
        return [
            ':CONTEXT' => $context,
        ];
    }

    /**
     * Youtube specific description prompt.
     */
    private static function youtube(): string
    {
        return <<<'PROMPT'
            Provide a concise description for a YouTube video of around 100 words, including the key points covered using following context: :CONTEXT.
            Only provide description for a youtube video, no title, no hash tags and no title and headings, just description text.
        PROMPT;
    }

    /**
     * Tiktok specific description prompt.
     */
    private static function tiktok(): string
    {
        return <<<'PROMPT'
            Provide a concise description for a TikTok video under 2000 words, including the key points covered using following context: :CONTEXT. 
            Do not provide any explanation or subtext. Just provide the description.
        PROMPT;
    }

    /**
     * LinkedIn specific description prompt.
     */
    private static function linkedin(): string
    {
        return <<<'PROMPT'
            Provide a concise description for a LinkedIn video, including the key points covered using following context: :CONTEXT. 
            Only provide description for a LinkedIn video, no title, no hash tags and no title and headings, just description text
        PROMPT;
    }

    /**
     * Facebook specific description prompt.
     */
    private static function facebook(): string
    {
        return <<<'PROMPT'
            Provide a concise description for a Facebook video, including the key points covered using following context: :CONTEXT. 
            Only provide description for a Facebook video, no title, no hash tags and no title and headings, just description text.
        PROMPT;
    }

    /**
     * Threads specific description prompt.
     */
    private static function threads(): string
    {
        return <<<'PROMPT'
            Provide a concise description for a Threads video under 70 words, including the key points covered using following context: :CONTEXT.
            Only provide description for a Threads video, no title, no hash tags and no title and headings, just description text.
        PROMPT;
    }

    /**
     * Instagram specific description prompt.
     */
    private static function instagram(): string
    {
        return <<<'PROMPT'
            Provide a concise description for an Instagram video, including the key points covered using following context: :CONTEXT.
            Only provide description for an Instagram video, no title, no hash tags and no title and headings, just description text.
        PROMPT;
    }

    /**
     * Default tags prompt.
     */
    private static function default(): string
    {
        return <<<'PROMPT'
            Provide a concise description for a social media video under 200 words, including the key points covered using following context: :CONTEXT.
            Only provide description for a social media video, no title, no hash tags and no title and headings, just description text.
        PROMPT;
    }
}
