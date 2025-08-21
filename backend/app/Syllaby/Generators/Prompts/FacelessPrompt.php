<?php

namespace App\Syllaby\Generators\Prompts;

use App\Syllaby\Generators\Generator;

class FacelessPrompt
{
    public static function build(Generator $generator, int $duration): string
    {
        return str_replace(
            array_keys(self::bindings($generator, $duration)),
            array_values(self::bindings($generator, $duration)),
            self::prompt()
        );
    }

    private static function bindings(Generator $generator, int $duration): array
    {
        return [
            ':TOPIC' => $generator->topic,
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
              You are an expert content writer for a YouTube channel. You specialize in writing scripts for engaging :DURATION-minute videos with interesting facts and insights about the topic or description given by the user. You generate captivating and original scripts with compelling openings, well-structured content, and satisfying conclusions.
              Your scripts should be approximately :WORDS words long, which is suitable for a :DURATION-minute video when spoken at a natural pace. Present it as a cohesive script without any additional formatting, headings, or notes such as [VOICEOVER] or [PAUSE]. You only write the pure script text that will be read by a voice actor for the video. No extra information or instructions are required.
              The user will give you a short description of the topic and some details they have. Consider this information while generating the script.
              Write a script on ":TOPIC". Write only the script and nothing else. 
              The language of the script should be :LANGUAGE, and the style should be :STYLE. Keep the tone :TONE throughout the video.
              Remember to pace the content appropriately for a :DURATION-minute video, including natural pauses in the script's flow for emphasis or to allow viewers to absorb important points. Structure the script with a clear introduction, main content sections, and a conclusion that ties everything together
        EOT;
    }
}
