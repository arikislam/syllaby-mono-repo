<?php

namespace App\Syllaby\Generators\Prompts;

use Illuminate\Support\Arr;
use App\Syllaby\Videos\Faceless;

class RephraseGenreImagePrompt
{
    public static function build(Faceless $faceless, array $images, int $index): string
    {
        return str_replace(
            array_keys(self::bindings($faceless, $images, $index)),
            array_values(self::bindings($faceless, $images, $index)),
            self::prompt()
        );
    }

    private static function bindings(Faceless $faceless, array $images, int $index): array
    {
        return [
            ':SCRIPT' => $faceless->script,
            ':IMAGES' => json_encode($images),
            ':ID' => Arr::get($images, "{$index}.id"),
        ];
    }

    private static function prompt(): string
    {
        return <<<'EOT'
            You are an expert in writing prompts for text-to-image AI models.
            
            Basic prompt format:
            "[Type of image] [Subject] [Environment] [Action]"
            
            Format Explanation:
            1) Type of image: This is like the "medium" of the image. For physical art, you often start with the medium, meaning the materials used, like paint, pencils, glass, stone, etc. In AI art, you also need to pick your materials first by sharing the type of image you want to make.
            2) Subject: This is the main focus of the image.
            3) Environment: This is the setting for the subject - it can be as simple as "pure white background" or more complex if needed. 
            4) Action: This is if the subject is doing something in particular or taking some action. If the subject isn't doing anything (e.g. creating an abstract logo), no need to add anything here.
            
            Example of prompt format:
            "Splash art digital painting wolf at night moon mountain background howling."
            
            Key Elements of a Good Prompt:
            1) Use comma separated terms.
            3) Make each image is different. For example, If they both contain people, then both should be oriented or facing different directions.
            4) keep the prompt within 60 words.
            5) Emotion: Convey the atmosphere or feeling.
            6) Consistency: Each prompt should be unique and in context with previous one.
            7) Avoid repetitive or similar descriptions. Always add a unique distinctive element that fits the overall context.
            
            Video script for context:
            ":SCRIPT"
            
            Current Generated Image Description Prompts:
            ":IMAGES"
               
            The previous JSON string contains the image id and image description originally generated.
            The description with image ID ":ID" is either out of context or its description is too similar to another image.
            Your job is to rephrase and improve the description of the image ID ":ID". 
            Keep the description short, very precise, pragmatic and avoid abstract terms. 
            Pick the most prominent keyword and build a description focused on it. 
            You can be creative to ensure a unique image description within the video script context.
            Ensure the unique elements are placed at the beginning of the description.
            
            Output the description and nothing else. No extra text or auxiliary information.

            Output:
        EOT;
    }
}
