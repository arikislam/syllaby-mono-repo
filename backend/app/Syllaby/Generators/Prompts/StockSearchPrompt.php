<?php

namespace App\Syllaby\Generators\Prompts;

class StockSearchPrompt
{
    public static function build(string $script): string
    {
        return str_replace(
            array_keys(self::bindings($script)),
            array_values(self::bindings($script)),
            self::prompt()
        );
    }

    private static function bindings(string $script): array
    {
        return [
            ':SCRIPT' => $script,
        ];
    }

    private static function prompt(): string
    {
        return <<<'EOT'
            You are a video script keyword analyzer. Your task is to extract the most relevant search term for stock media based on the provided script.

            Analyze the script for these key elements (in order of priority):
            1. Primary visual subject (person, object, location)
            2. Main action or event
            3. Dominant emotion or mood
            4. Time period or setting
            5. Abstract concept or theme

            Guidelines:
            - Output exactly 1-3 words maximum
            - Use common stock media search terminology
            - Focus on visually representable elements
            - Avoid abstract or non-visual concepts unless explicitly required
            - Prioritize specific over generic terms
            - Use singular form unless plurality is crucial
            - Exclude articles, prepositions, and conjunctions

            Example script-to-keyword pairs:
            "A businessman rushing through a crowded subway station" -> "commuter rushing"
            "Sunlight streaming through autumn leaves in a forest" -> "forest sunbeams"
            "A mother helping her child with homework at the kitchen table" -> "parenting homework"
            "Waves crashing against rocky cliffs at sunset" -> "coastal sunset"
            "An elderly couple walking through a park holding hands" -> "elderly romance"
            "A chef carefully plating a gourmet dish" -> "chef plating"
            "Time-lapse of a bustling city at night" -> "cityscape timelapse"
            "A young athlete training alone in a gym" -> "athlete training"

            Input script: :SCRIPT

            Output format: Just the search term(s) without any additional text or formatting.
        EOT;
    }
}
