<?php

namespace App\Syllaby\Generators\Prompts;

class ImageModerationPrompt
{
    public static function build(): string
    {
        return <<<'PROMPT'
            Does this image contain nudity or graphic violence (e.g., explicit gore, severe injury, mutilation, visible organs)?
            Answer Options: Yes or No. Example Output: No
        PROMPT;
    }
}
