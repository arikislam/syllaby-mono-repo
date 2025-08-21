<?php

namespace App\Syllaby\Schedulers\DTOs;

use Illuminate\Support\Arr;
use App\Syllaby\Videos\Enums\TextPosition;
use Illuminate\Contracts\Support\Arrayable;

class Caption implements Arrayable
{
    public function __construct(
        public string $font_family,
        public string $position,
        public string $effect,
        public string $font_color,

    ) {}

    /**
     * Create a new caption from the scheduler input.
     */
    public static function fromScheduler(array $input): self
    {
        return new self(
            font_family: Arr::get($input, 'font_family', 'lively'),
            position: Arr::get($input, 'position', TextPosition::CENTER->value),
            effect: Arr::get($input, 'effect', 'none'),
            font_color: Arr::get($input, 'font_color', 'default'),
        );
    }

    /**
     * Array representation of the caption.
     */
    public function toArray(): array
    {
        return [
            'font_family' => $this->font_family,
            'position' => $this->position,
            'effect' => $this->effect,
            'font_color' => $this->font_color,
        ];
    }
}
