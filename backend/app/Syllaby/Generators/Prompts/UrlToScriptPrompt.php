<?php

namespace App\Syllaby\Generators\Prompts;

use App\Syllaby\Generators\Generator;
use App\Syllaby\Scraper\DTOs\ScraperResponseData;

class UrlToScriptPrompt
{
    public static function build(Generator $generator, int $duration, string $content): string
    {
        return str_replace(
            array_keys(self::bindings($generator, $duration, $content)),
            array_values(self::bindings($generator, $duration, $content)),
            self::prompt()
        );
    }

    private static function bindings(Generator $generator, int $duration, string $content): array
    {
        return [
            ':CONTENT' => $content,
            ':LANGUAGE' => $generator->language,
            ':STYLE' => $generator->style,
            ':TONE' => $generator->tone,
            ':DURATION' => $duration / 60,
            ':WORDS' => words_count($duration),
        ];
    }

    private static function prompt(): string
    {
        return <<<'EOT'
              You are an expert content writer for a YouTube channel. You specialize in writing scripts for engaging :DURATION-minute videos with interesting facts and insights about the markdown content of url provided by the user. You generate captivating and original scripts with compelling openings, well-structured content, and satisfying conclusions.
              Your scripts should be approximately :WORDS words long, which is suitable for a :DURATION-minute video when spoken at a natural pace. Present it as a cohesive script without any additional formatting, headings, or notes such as [VOICEOVER] or [PAUSE]. You only write the pure script text that will be read by a voice actor for the video. No extra information or instructions are required.
              The user will give you a short description of the topic and some details they have. Consider this information while generating the script.
              Following is markdown content ":CONTENT". 
              Write only the script and nothing else. Ensure to avoid any irrelevant data from the markdown content of the url provided by the user.
              The language of the script should be :LANGUAGE, and the style should be :STYLE. Keep the tone :TONE throughout the video.
              Remember to pace the content appropriately for a :DURATION-minute video, including natural pauses in the script's flow for emphasis or to allow viewers to absorb important points. Structure the script with a clear introduction, main content sections, and a conclusion that ties everything together
        EOT;
    }
}
