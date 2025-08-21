<?php

namespace App\Syllaby\Generators\Prompts;

use App\Syllaby\Generators\Generator;

class FacelessScriptPrompt
{
    public static function build(Generator $generator, string $heading): string
    {
        return str_replace(
            array_keys(self::bindings($generator, $heading)),
            array_values(self::bindings($generator, $heading)),
            self::prompt()
        );
    }

    private static function bindings(Generator $generator, string $heading): array
    {
        return [
            ':TOPIC' => $generator->topic,
            ':HEADING' => $heading,
        ];
    }

    private static function prompt(): string
    {
        return <<<'EOT'
            For the following heading from an outline on the topic of ":TOPIC":
            :HEADING
            Please provide a detailed explanation of that includes:
            A clear definition or description of the concept introduced in this heading.
            The significance or importance of this point in relation to the main topic.
            At least two specific examples or real-world applications that illustrate this point.
            Any relevant opinions that support this idea (if applicable).
            Potential challenges, controversies, or alternative viewpoints associated with this concept (if any).
            How this point connects to or influences other aspects of the main topic. Just plain text without any headings or sub-headings
            Please provide this information in a clear, concise manner suitable for script writing. Aim for a response between 350-400 words that captures the essence of the heading and provides valuable insights for the audience.
        EOT;
    }
}
