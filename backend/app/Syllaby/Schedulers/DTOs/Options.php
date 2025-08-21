<?php

namespace App\Syllaby\Schedulers\DTOs;

use Illuminate\Support\Arr;
use App\Syllaby\Videos\Enums\Sfx;
use App\Syllaby\Videos\Enums\Overlay;
use Illuminate\Contracts\Support\Arrayable;

class Options implements Arrayable
{
    public function __construct(
        public ?int $voice_id,
        public ?int $duration,
        public ?string $sfx,
        public ?string $language,
        public ?string $aspect_ratio,
        public ?string $transition,
        public ?string $overlay,
        public ?Caption $captions,
        public ?string $genre,
        public ?int $music_id,
        public ?string $music_volume,
        public ?string $background_id,
    ) {}

    /**
     * Create a new options from the model.
     */
    public static function fromModel(array $values): self
    {
        return new self(
            voice_id: Arr::get($values, 'voice_id'),
            duration: Arr::get($values, 'duration'),
            sfx: Arr::get($values, 'sfx'),
            language: Arr::get($values, 'language'),
            aspect_ratio: Arr::get($values, 'aspect_ratio'),
            transition: Arr::get($values, 'transition'),
            overlay: Arr::get($values, 'overlay'),
            captions: Caption::fromScheduler($values['captions']),
            genre: Arr::get($values, 'genre'),
            music_id: Arr::get($values, 'music_id'),
            music_volume: Arr::get($values, 'music_volume'),
            background_id: Arr::get($values, 'background_id'),
        );
    }

    /**
     * Create a new options from the scheduler input.
     */
    public static function fromRequest(array $input): self
    {
        return new self(
            voice_id: Arr::get($input, 'options.voice_id'),
            duration: Arr::get($input, 'options.duration', 60),
            sfx: Arr::get($input, 'options.sfx', Sfx::NONE->value),
            language: Arr::get($input, 'options.language', 'English'),
            aspect_ratio: Arr::get($input, 'options.aspect_ratio', '9:16'),
            transition: Arr::get($input, 'options.transition', 'none'),
            overlay: Arr::get($input, 'options.overlay', Overlay::NONE->value),
            captions: Caption::fromScheduler(Arr::get($input, 'captions', [])),
            genre: Arr::get($input, 'options.genre_id'),
            music_id: Arr::get($input, 'options.music_id'),
            music_volume: Arr::get($input, 'options.music_volume'),
            background_id: Arr::get($input, 'options.background_id'),
        );
    }

    /**
     * Array representation of the options.
     */
    public function toArray(): array
    {
        return [
            'voice_id' => $this->voice_id,
            'duration' => $this->duration,
            'sfx' => $this->sfx,
            'language' => $this->language,
            'aspect_ratio' => $this->aspect_ratio,
            'transition' => $this->transition,
            'overlay' => $this->overlay,
            'captions' => $this->captions?->toArray(),
            'genre' => $this->genre,
            'music_id' => $this->music_id,
            'music_volume' => $this->music_volume,
            'background_id' => $this->background_id,
        ];
    }
}
