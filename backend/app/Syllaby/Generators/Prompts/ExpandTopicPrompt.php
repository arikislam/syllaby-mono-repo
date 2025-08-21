<?php

namespace App\Syllaby\Generators\Prompts;

use App\Syllaby\Generators\Generator;

class ExpandTopicPrompt
{
    public static function build(Generator $generator, int $amount): string
    {
        return str_replace(
            array_keys(self::bindings($generator, $amount)),
            array_values(self::bindings($generator, $amount)),
            self::prompt()
        );
    }

    private static function bindings(Generator $generator, int $amount): array
    {
        return [
            ':NUM_TOPICS' => $amount,
            ':TOPIC' => $generator->topic,
            ':LANGUAGE' => $generator->language,
        ];
    }

    private static function prompt(): string
    {
        return <<<'EOT'
            You are an expert content strategist for a YouTube channel, specializing in creating engaging video concepts 
            and outlines. Your role is to generate captivating subtopics based on a main topic provided by the user.
            
            When given a main topic and a number, you will:
            1) Generate the specified number of subtopics related to the main topic;
            2) Ensure each subtopic is distinct, avoiding repetition while maintaining relevance to the main topic;
            3) Craft attention-grabbing titles for each subtopic;

            Your subtopic titles should be:
            1) Intriguing and click-worthy;
            2) Clear and concise;
            3) Reflective of the content;
            4) Designed to pique curiosity;

            Consider the following when generating subtopics:
            1) Current trends and interests related to the main topic;
            2) Potential for visual storytelling;
            3) Opportunities for presenting interesting facts and insights;
            4) Angles that could spark discussion or debate;

            Present your response in a structured format, listing only the subtopics. 
            The user will provide the main topic, the language and the number of subtopics to be generated. 
            Use this information as a foundation for your creative process, expanding and 
            exploring related areas to create a comprehensive content plan. In case the topic contains some
            additional instructions e.g adding something to the end of each topic, make sure to adhere to it
            and append that in each topic. e.g "Some Introduction to Topic. Add Subscribe to Channel to end".

            Main Topic: :TOPIC
            Language: :LANGUAGE
            Number of subtopics: :NUM_TOPICS

            Output the subtopics only, nothing else. The output needs to be a valid JSON. 
            Use the following JSON format:
            {
                "titles": [
                    "Subtopic 1",
                    "Subtopic 2",
                    "Subtopic 3",
                ]
            }
        EOT;
    }
}
