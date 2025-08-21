<?php

namespace App\Syllaby\Videos\Casts;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use App\Syllaby\Videos\Enums\Sfx;
use App\Syllaby\Videos\DTOs\Options;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Videos\Enums\TextPosition;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

class OptionsCast implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Options
    {
        $value = json_decode($value, true);

        return new Options(
            font_family: Arr::get($value, 'font_family', 'Montserrat'),
            position: Arr::get($value, 'position', TextPosition::CENTER->value),
            aspect_ratio: Arr::get($value, 'aspect_ratio', '9:16'),
            sfx: Arr::get($value, 'sfx', Sfx::NONE->value),
            font_color: Arr::get($value, 'font_color', 'default'),
            volume: Arr::get($value, 'volume'),
            transition: Arr::get($value, 'transition', 'none'),
            caption_effect: Arr::get($value, 'caption_effect', 'none'),
            overlay: Arr::get($value, 'overlay', 'none'),
            voiceover: Arr::get($value, 'voiceover'),
            watermark_position: Arr::get($value, 'watermark_position'),
            watermark_opacity: Arr::get($value, 'watermark_opacity'),
        );
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! $value instanceof Options) {
            throw new InvalidArgumentException('The given value is not an Options instance.');
        }

        return json_encode([
            'font_family' => $value->font_family,
            'position' => $value->position,
            'aspect_ratio' => $value->aspect_ratio,
            'font_color' => $value->font_color,
            'sfx' => $value->sfx,
            'volume' => $value->volume,
            'transition' => $value->transition,
            'caption_effect' => $value->caption_effect,
            'overlay' => $value->overlay,
            'voiceover' => $value->voiceover,
            'watermark_position' => $value->watermark_position,
            'watermark_opacity' => $value->watermark_opacity,
        ]);
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): Options
    {
        $value = json_encode($value);

        return $this->get($model, $key, $value, $attributes);
    }
}
