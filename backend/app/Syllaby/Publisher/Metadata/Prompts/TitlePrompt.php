<?php

namespace App\Syllaby\Publisher\Metadata\Prompts;

use Illuminate\Support\Str;

class TitlePrompt
{
    /**
     * Generate the title prompt.
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
     * Bindings for the title prompt.
     */
    private static function bindings(string $context): array
    {
        return [
            ':CONTEXT' => $context,
        ];
    }

    /**
     * Youtube specific title prompt.
     */
    private static function youtube(): string
    {
        return <<<'PROMPT'
            Generate a concise and captivating title for a YouTube video using following context: :CONTEXT.
            Do not provide any explanation or subtext. Just provide the title. Do not exceed 100 characters.
        PROMPT;
    }

    /**
     * Tiktok specific title prompt.
     */
    private static function tiktok(): string
    {
        return <<<'PROMPT'
            Generate a concise and captivating title for a TikTok video using following context: :CONTEXT.
            Do not provide any explanation or subtext. Just provide the title. Do not exceed 100 characters.
        PROMPT;
    }

    /**
     * LinkedIn specific title prompt.
     */
    private static function linkedin(): string
    {
        return <<<'PROMPT'
            Generate a concise and captivating title for a LinkedIn video using following context: :CONTEXT.
            Do not provide any explanation or subtext. Just provide the title. Do not exceed 100 characters.
        PROMPT;
    }

    /**
     * Facebook specific title prompt.
     */
    private static function facebook(): string
    {
        return <<<'PROMPT'
            Generate a concise and captivating title for a Facebook video using following context: :CONTEXT.
            Do not provide any explanation or subtext. Just provide the title. Do not exceed 100 characters.
        PROMPT;
    }

    /**
     * Threads specific title prompt.
     */
    private static function threads(): string
    {
        return <<<'PROMPT'
            Generate a concise and captivating title for a Threads video using following context: :CONTEXT.
            Do not provide any explanation or subtext. Just provide the title. Do not exceed 100 characters.
        PROMPT;
    }

    /**
     * Instagram specific title prompt.
     */
    private static function instagram(): string
    {
        return <<<'PROMPT'
            Generate a concise and captivating title for an Instagram video using following context: :CONTEXT.
            Do not provide any explanation or subtext. Just provide the title. Do not exceed 100 characters.
        PROMPT;
    }

    /**
     * Default title prompt.
     */
    private static function default(): string
    {
        return <<<'PROMPT'
            Generate a concise and captivating title for a social media video using following context: :CONTEXT.
            Do not provide any explanation or subtext. Just provide the title. Do not exceed 100 characters.
        PROMPT;
    }
}
