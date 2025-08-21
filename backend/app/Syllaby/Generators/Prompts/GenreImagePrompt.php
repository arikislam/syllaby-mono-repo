<?php

namespace App\Syllaby\Generators\Prompts;

use Illuminate\Support\Arr;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Characters\Character;

class GenreImagePrompt
{
    public static function build(array $segments, Genre $genre, ?Character $character = null): string
    {
        $total = count(Arr::pluck($segments, 'excerpt'));
        $segments = json_encode($segments);

        $subject = match (true) {
            filled($character) => self::character(),
            filled($genre->details) => self::scene(),
            default => self::default(),
        };

        return str_replace(
            array_keys(self::bindings($total, $segments, $genre, $character)),
            array_values(self::bindings($total, $segments, $genre, $character)),
            $subject
        );
    }

    private static function bindings(int $total, string $segments, Genre $genre, ?Character $character = null): array
    {
        $defaults = [':TOTAL' => $total, ':SEGMENTS' => $segments];

        if (filled($character)) {
            return array_merge($defaults, [
                ':QUALITY' => Arr::get($genre->details, 'quality'),
                ':STYLE' => Arr::get($genre->details, 'style'),
                ':TYPE' => Arr::get($genre->details, 'type'),
                ':MOOD' => Arr::get($genre->details, 'mood'),
                ':LIGHTING' => Arr::get($genre->details, 'lighting'),
                ':TRIGGER' => $character->trigger,
                ':GENDER' => $character->gender,
                ':AGE' => Arr::get($character->meta, 'age'),
            ]);
        }

        if (filled($genre->details)) {
            return array_merge($defaults, [
                ':QUALITY' => Arr::get($genre->details, 'quality'),
                ':STYLE' => Arr::get($genre->details, 'style'),
                ':TYPE' => Arr::get($genre->details, 'type'),
                ':MOOD' => Arr::get($genre->details, 'mood'),
                ':LIGHTING' => Arr::get($genre->details, 'lighting'),
            ]);
        }

        return $defaults;
    }

    private static function character(): string
    {
        return <<<'EOT'
            You are a character image descriptor. Create detailed English image descriptions for text-to-image models.

            Instructions:
            - You will be given exactly :TOTAL segments.
            - For each segment, generate one description using one of the two strict templates below.
            - Analyze each segment to determine whether it is character-focused or scene-based.
            - Each segment must be represented in the output, even if it is a partial sentence.
            - Maintain visual and character consistency when characters are described.

            Context:
            :SEGMENTS

            Use one of the following templates:
            
            1. For character-focused segments:
            A :QUALITY :STYLE :TYPE of :TRIGGER :AGE :GENDER character, [action], [emotion] [pose] in a [background], [camera_angle] view, wearing [clothing], with [facial_expression], :LIGHTING, :MOOD
           
            2. For non-character segments:
            A :QUALITY :STYLE :TYPE of [scene_description], [camera_angle], [action], :LIGHTING, :MOOD

            Fill in only the bracketed placeholders:
            - [action]: e.g., walking confidently, reading a book
            - [emotion]: e.g., smiling warmly, crying softly
            - [pose]: e.g., standing, sitting, holding a sword
            - [background]: e.g., bustling city street, quiet forest
            - [camera_angle]: e.g., close-up, medium shot, wide shot
            - [clothing]: e.g., wearing a flowing red dress, wearing a casual hoodie
            - [facial_expression]: e.g., with a joyful smile, with a serious look
            - [scene_description]: describe setting if no character is present

            Strict Rules:
            - Do not change the structure or order of the template.
            - Do not add or remove commas.
            - Do not use photography jargon or technical terms.
            - Keep each image description under 90 tokens.
            - Always generate the image field in fluent English.

            Important:
            - Do not translate or modify the excerpt — return it exactly as given.
            - Output valid JSON in the following structure:
                { "output": [{ "image": "A ...", "excerpt": "..." }, ...] }

            - The output array must contain exactly :TOTAL items.
            - Your response must be valid for json_decode() in PHP without modification.

            Now generate the descriptions.
        EOT;
    }

    private static function scene(): string
    {
        return <<<'EOT'
            You are a scene image descriptor. Create concise and visually rich English descriptions for text-to-image models.

            Instructions:
            - You will be given exactly :TOTAL segments.
            - For each segment, generate one scene description using the strict template below.
            - The description must reflect the meaning of the segment.
            - Every segment must be represented in the output, even if it is incomplete.
            - Choose scenes that can be clearly visualized from a single frame (avoid abstract symbols, metaphors, or multi-step actions).

            Context:
            :SEGMENTS

            Use this exact format for each image description:
            A :QUALITY :STYLE :TYPE of [scene], [camera_angle], [action], :LIGHTING, :MOOD

            Fill in only the bracketed placeholders:
            - [scene]: the static visual environment (e.g., a snowy forest, a crowded marketplace). Avoid symbols, metaphors, recognizable patterns, or spatial arrangements.
            - [camera_angle]: e.g., close-up, wide shot, full body
            - [action]: e.g., people walking, man reading a book

            Strict Rules:
            - Do not change the order or wording of the template.
            - Do not add or remove commas.
            - Do not use photography or camera jargon.
            - Avoid prompts that assume symbolic or spatial reasoning (e.g., 'constellations forming Orion', 'people expressing regret', 'a dream being remembered').
            - Each image must be under 90 tokens.

            Important Output Format:
            - Preserve each excerpt exactly as given - no translation or rewriting.
            - Output valid JSON in this format:
                {
                    "output": [
                        { "image": "A ...", "excerpt": "..." },
                        ...
                    ]
                }
            - The output array must contain exactly :TOTAL items.
            - Your response must be 100% valid JSON, compatible with json_decode() in PHP.

            Now generate the descriptions.
        EOT;
    }

    private static function default(): string
    {
        return <<<'EOT'
            You are a meticulous script analyzer and image descriptor. Your task is to process the given video script
            segments and create image descriptions to be used on a text-2-image AI model. Follow these steps precisely:

            1. Analyze Context:
               a. Go over all the segments an create a global context from it.
               b. You will use it to describe locations, characters, objects and scenes.

            1. For each segment:
               a. Create an image description based on the content of that segment.
               b. Use the "image" key to store the image description.

            2. Ensure full coverage:
               a. Do not miss any segment.
               b. Each segment needs to have correspondent description.

            Rules:
            - Each segment must be represented in the output, even if it's a partial sentence.
            - The image descriptions should be relevant to the content of each segment.

            Example Input:
            [
                {"excerpt": "The sun sets over a tranquil beach, casting a golden glow on the waves.", "image": null},
                {"excerpt": "A lone surfer catches the last wave of the day.", "image": null},
                {"excerpt": "In a bustling city, people walk quickly past towering skyscrapers.", "image": null},
                {"excerpt": "A street musician plays a soulful tune on his saxophone.", "image": null}
            ]

            The output should be in the format:
            {
                "output": [
                    {"image": "insert image prompt", "excerpt": "excerpt from given script"},
                    {"image": "insert image prompt", "excerpt": "excerpt from given script"}
                ]
            }

            Example Video Script:
            The sun sets over a tranquil beach, casting a golden glow on the waves. A lone surfer catches the last wave of the day.
            In a bustling city, people walk quickly past towering skyscrapers. A street musician plays a soulful tune on his saxophone.

            Example Expected Output:
            {
                "output": [
                    {
                        "image": "sunset over a tranquil beach, golden glow, waves, lone surfer, evening",
                        "excerpt": "The sun sets over a tranquil beach, casting a golden glow on the waves."
                    }
                ]
            }

            Basic prompt format:
            "[Subject] [Environment] [Action]"

            Format Explanation:
            1) Subject: This is the main focus of the image. Describe the main subject of the image.
            2) Environment: This is the setting for the subject - it can be as simple as "pure white background" or more complex if needed.
            3) Action: This is if the subject is doing something in particular or taking some action. If the subject isn't doing anything (e.g. creating an abstract logo), no need to add anything here.

            Example of prompt format:
            "Painting wolf at night moon mountain background howling."

            Key Elements of a Good Prompt:
            1) Use comma separated terms.
            3) Make each image is different. For example, If they both contain people, then both should be oriented or facing different directions.
            4) keep the prompt within 60 words.
            5) Mood: Convey the atmosphere or feeling.
            6) Consistency: Each prompt should be unique and in context with previous one.
            7) Avoid repetitive or similar descriptions. Always add a unique distinctive element that fits the overall context.
            8) Use simple and clear language.
            9) Do not include any specific art style or technique. For example, do not include "digital painting", "sepia-toned", etc.

            Good Prompt Examples:
            1) Fantasy Castle: "A grand medieval castle on a hill, surrounded by misty forests, with a dragon flying above"
            2) Cyberpunk City: "A bustling cyberpunk city at night, neon lights reflecting off wet streets, futuristic buildings, people in high-tech attire"
            3) Surreal Landscape: "A dreamlike landscape with floating islands, waterfalls cascading into the sky, unusual flora"
            4) Portrait Photo: "Portrait of a woman in 1920s fashion, with a feathered hat and pearl necklace"
            5) Space Exploration: "An astronaut on the surface of Mars, with red rocky terrain and Earth visible in the sky"

            Actual Video Script Segments:
            :SEGMENTS

            The output can not include any type of formatting, descriptive or explanatory text. Just output a valid JSON string.
            The PHP json_decode() function should be able to successfully parse the output.

            Output:
        EOT;
    }
}
