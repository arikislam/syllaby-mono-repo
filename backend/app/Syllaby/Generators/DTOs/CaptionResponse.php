<?php

namespace App\Syllaby\Generators\DTOs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Vendors\Faceless\Speech\ElevenLabsTranscriptions;

class CaptionResponse
{
    /**
     * Create a DTO instance.
     */
    public function __construct(public array $captions) {}

    /**
     * Acts as static factory and builds a new response instance from Elevenlabs data.
     */
    public static function fromElevenlabs(array $transcriptions): self
    {
        $words = (new ElevenLabsTranscriptions)->parse($transcriptions);

        return new self([
            'text' => Str::trim(collect($words)->pluck('text')->implode(' ')),
            'start' => Arr::get(head($words), 'start'),
            'end' => Arr::get(last($words), 'end'),
            'words' => $words,
        ]);
    }

    /**
     * Acts as static factory and builds a new response instance from Whisper data.
     */
    public static function fromWhisper(array $response): self
    {
        $words = Arr::map(Arr::get($response, 'chunks'), fn ($word) => [
            'text' => Str::trim(Arr::get($word, 'text')),
            'start' => Arr::get($word, 'timestamp.0'),
            'end' => Arr::get($word, 'timestamp.1'),
        ]);

        return new self([
            'text' => Str::trim(Arr::get($response, 'text')),
            'start' => Arr::get(Arr::first($words), 'start'),
            'end' => Arr::get(Arr::last($words), 'end'),
            'words' => $words,
        ]);
    }

    /**
     * Returns the array representation of the response.
     */
    public function toArray(): array
    {
        return $this->captions;
    }
}
