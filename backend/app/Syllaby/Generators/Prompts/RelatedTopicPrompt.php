<?php

namespace App\Syllaby\Generators\Prompts;

class RelatedTopicPrompt
{
    /**
     * Build the prompt for generating related topics and replaces the placeholders with values.
     */
    public static function build(string $topic, ?string $language = null): string
    {
        return str_replace(
            array_keys(self::bindings($topic, $language)),
            array_values(self::bindings($topic, $language)),
            self::prompt()
        );
    }

    /**
     * Get the bindings for the prompt.
     */
    private static function bindings(string $topic, ?string $language = null): array
    {
        return [
            ':TOPIC' => $topic,
            ':LANGUAGE' => $language,
        ];
    }

    /**
     * Get the prompt for generating related topics.
     */
    private static function prompt(): string
    {
        return <<<'EOT'
            Act as an expert SEO keyword researcher. Your goal is to write related video topics that get a high click-though rate.

            Rules:
            - The suggested topics should be in the same niche, but on different topics. 
            - The suggested topics needs to be in :LANGUAGE language.
            - If the value of :LANGUAGE is not provided (null), inspect the topic and suggest topics in the same language.
            - They should have an emphasis on SEO, but also be a little click-bait to cause intrigue.

            Here is the topic of the last video I created: :TOPIC

            Please suggest 5 new video titles in that niche.

            Here are examples of good titles for reference:
            1. What Caused the Roman Empire to Collapse
            2. How Rome Forged an Epic Empire 
            3. The Ancient Rome Iceberg Explained
            4. What did the Ancient Romans eat?
            5. What Did Ancient Rome Look Like?
            6. The Complete History of Rome, Summarized
            7. What It Was Like To Live In Ancient Rome During Its Golden Age
            8. What Made The Ancient Roman Empire So Successful?
            9. THE ROMAN EMPIRE NEVER EXISTED - SHOCKING TRUTH
            10. The Fall of Rome Explained In 13 Minutes

            Structure output as follows:
            {
                "topics": [
                    "topic title 1",
                    "topic title 2",
                    "topic title 3",
                    "topic title 4",
                    "topic title 5"
                ]
            }

            The output can not include any type of formatting, descriptive or explanatory text. 
            Just output a valid JSON string. 

            Output:
        EOT;
    }
}
