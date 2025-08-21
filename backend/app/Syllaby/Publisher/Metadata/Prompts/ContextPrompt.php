<?php

namespace App\Syllaby\Publisher\Metadata\Prompts;

class ContextPrompt
{
    /**
     * Generate the context prompt.
     */
    public static function generate(string $data): string
    {
        return str_replace(
            array_keys(self::bindings($data)),
            array_values(self::bindings($data)),
            self::prompt()
        );
    }

    /**
     * Bindings for the context prompt.
     */
    private static function bindings(string $data): array
    {
        return [
            ':DATA' => $data,
        ];
    }

    /**
     * Prompt for the context.
     */
    private static function prompt(): string
    {
        return <<<'PROMPT'
            Write me a description for a social media post based on the following information: :DATA. 
            Only provide a text description, no title, no hashtags and no headings, just description text.
        PROMPT;
    }
}
