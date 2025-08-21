<?php

namespace App\Syllaby\Assets\Rules;

use Closure;
use FFMpeg\FFProbe;
use RuntimeException;
use Illuminate\Contracts\Validation\ValidationRule;

class AudioDuration implements ValidationRule
{
    /**
     * Create a rule instance.
     */
    public function __construct(protected int $limit) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $buffer = 18;
        $ffprobe = $this->ffprobe();
        $source = $value->getRealPath();

        if (! $ffprobe->isValid($source)) {
            $fail('Invalid audio file.');
        }

        try {
            $audio = $ffprobe->streams($source)->audios()->first();
            $duration = (float) $audio->get('duration');

            if ($duration > $this->limit + $buffer) {
                $fail($this->message());
            }
        } catch (RuntimeException) {
            $fail('It was not possible to get the audio duration.');
        }
    }

    /**
     * Get the validation error message.
     */
    private function message(): string
    {
        $minutes = floor($this->limit / 60);

        return "The audio must not exceed {$minutes} minutes.";
    }

    /**
     * Get the ffmpeg instance.
     */
    private function ffprobe(): FFProbe
    {
        return FFProbe::create([
            'ffprobe.binaries' => config('media-library.ffprobe_path'),
        ]);
    }
}
