<?php

namespace App\Syllaby\Generators\Prompts;

use App\Syllaby\Generators\Generator;

class ScriptPrompt
{
    public static function build(Generator $generator): string
    {
        $bindings = static::bindings($generator);

        $prompt = config('prompt.script_without_outline');

        $replaced = str_replace(array_keys($bindings), array_values($bindings), $prompt);

        return sprintf("%s \n %s", $replaced, static::guidelines($generator));
    }

    private static function bindings(Generator $generator): array
    {
        return [
            ':language' => $generator->language,
            ':words' => (int) $generator->length * 2,
            ':tone' => $generator->tone,
            ':style' => $generator->style,
            ':topic' => $generator->topic,
        ];
    }

    private static function guidelines(Generator $generator): string
    {
        return <<<EOT
                 The script should contain
                 1. A strong & unique  opening hook about "$generator->topic"
                 2. Address the problem about "$generator->topic"
                 3. Propose a solution about "$generator->topic"
                 4. A closing call to action to leave a comment or send a message if they would like to learn more about "{$generator->topic}"

                Good opening hook examples:
                 1. This may be controversial but...
                 2. VA Loans can save you a LOT of money! Here's why
                 3. Instead of buying this, buy this!
                 4. Everything you knew about (topic) is 100% WRONG!
                 5. Stop scrolling if you want to do ___
                 6. Exposing secret information about ___

                Examples of good closing call to actions:
                 1. Have any specific questions about TOPIC? Leave a comment below!
                 2. Make sure to follow so you can learn more about TOPIC
                 3. Share this video with yourself so you can re-watch it and take action!
                 4. Share this video with someone who could really benefit from this!

                 Do not include any quotes inside script.
                 Return only the script content.
                 Script should never exceed 3000 characters including spaces.
                 Do not include any kind of shot descriptions or section descriptions like: Opening Hook, Problem, Solution, Closing Call to Action
        EOT;
    }
}
