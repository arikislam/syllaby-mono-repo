<?php

namespace App\Syllaby\Generators\Prompts;

use App\Syllaby\Generators\Generator;

class FacelessOutlinePrompt
{
    public static function build(Generator $generator): string
    {
        return str_replace(
            array_keys(self::bindings($generator)),
            array_values(self::bindings($generator)),
            self::prompt()
        );
    }

    private static function bindings(Generator $generator): array
    {
        return [
            ':TOPIC' => $generator->topic,
            ':LANGUAGE' => $generator->language,
        ];
    }

    private static function prompt(): string
    {
        return <<<'EOT'
            Generate a structured outline for the topic ":TOPIC" with exactly 8 main headings in the specified language ":LANGUAGE".
            Ignore the irrelevant details and focus on the key points.

            The outline should:
            1. Begin with an introduction that sets the context
            2. Cover key aspects, concepts, or stages related to the topic
            3. Progress logically from basic to more advanced ideas
            4. Include practical applications or real-world examples
            5. Address potential challenges or controversies
            6. Conclude with future implications or a summary
            Ensure each heading is clear, concise, and directly related to the main topic. Don't Number the headings at all. Do not include any subheadings or additional explanations beyond the 8 main points.
            Your response should follow this format:
            [First heading]
            [Second heading]
            [Third heading]
            [Fourth heading]
            [Fifth heading]
            [Sixth heading]
            [Seventh heading]
            [Eighth heading]
        EOT;
    }
}
