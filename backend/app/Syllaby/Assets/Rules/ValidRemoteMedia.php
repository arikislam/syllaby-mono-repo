<?php

namespace App\Syllaby\Assets\Rules;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRemoteMedia implements ValidationRule
{
    private array $image = ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/webp'];

    private array $audio = ['audio/mp3', 'audio/wav', 'audio/mpeg', 'audio/webm'];

    private array $video = ['video/webm', 'video/mov', 'video/mp4'];

    public function __construct(protected readonly array $details)
    {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        match (true) {
            $this->fileTooBig() => $fail('The file exceeds the 200MB size limit'),
            $this->invalidFormat() => $fail('Unsupported file format'),
            default => null
        };
    }

    /**
     * Checks for file size.
     */
    private function fileTooBig(): bool
    {
        $size = Arr::get($this->details, 'size', PHP_INT_MAX);

        return $size > config('media-library.max_file_size');
    }

    /**
     * Checks for mime type.
     */
    private function invalidFormat(): bool
    {
        $type = Arr::get($this->details, 'mime-type');

        return !in_array($type, [...$this->video, ...$this->image, ...$this->audio]);
    }
}
