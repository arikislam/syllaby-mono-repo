<?php

namespace App\Syllaby\Videos\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class Options implements Arrayable
{
    public function __construct(
        public string $font_family,
        public string $position,
        public string $aspect_ratio,
        public ?string $sfx = 'none',
        public ?string $font_color = 'default',
        public ?string $volume = null,
        public ?string $transition = 'none',
        public ?string $caption_effect = 'none',
        public ?string $overlay = 'none',
        public ?int $voiceover = null,
        public ?string $watermark_position = null,
        public ?string $watermark_opacity = null,
    ) {}

    public function toArray(): array
    {
        return [
            'font_family' => $this->font_family,
            'position' => $this->position,
            'aspect_ratio' => $this->aspect_ratio,
            'sfx' => $this->sfx,
            'font_color' => $this->font_color,
            'volume' => $this->volume,
            'transition' => $this->transition,
            'caption_effect' => $this->caption_effect,
            'overlay' => $this->overlay,
            'voiceover' => $this->voiceover,
            'watermark_position' => $this->watermark_position,
            'watermark_opacity' => $this->watermark_opacity,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
