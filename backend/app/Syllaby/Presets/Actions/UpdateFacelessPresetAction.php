<?php

namespace App\Syllaby\Presets\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Presets\FacelessPreset;

class UpdateFacelessPresetAction
{
    /**
     * Update an existing faceless preset.
     */
    public function handle(FacelessPreset $preset, array $input): FacelessPreset
    {
        return tap($preset)->update([
            'voice_id' => Arr::get($input, 'voice_id', $preset->voice_id),
            'music_id' => Arr::get($input, 'music_id', $preset->music_id),
            'music_category_id' => Arr::get($input, 'music_category_id', $preset->music_category_id),
            'background_id' => Arr::get($input, 'background_id', $preset->background_id),
            'resource_id' => Arr::get($input, 'resource_id', $preset->resource_id),
            'genre_id' => Arr::get($input, 'genre_id', $preset->genre_id),
            'name' => Arr::get($input, 'name', $preset->name),
            'language' => Arr::get($input, 'language', $preset->language),
            'font_family' => Arr::get($input, 'font_family', $preset->font_family),
            'font_color' => Arr::get($input, 'font_color', $preset->font_color),
            'position' => Arr::get($input, 'position', $preset->position),
            'caption_animation' => Arr::get($input, 'caption_animation', $preset->caption_animation),
            'duration' => Arr::get($input, 'duration', $preset->duration),
            'orientation' => Arr::get($input, 'orientation', $preset->orientation),
            'transition' => Arr::get($input, 'transition', $preset->transition),
            'volume' => Arr::get($input, 'volume', $preset->volume),
            'sfx' => Arr::get($input, 'sfx', $preset->sfx),
            'overlay' => Arr::get($input, 'overlay', $preset->overlay),
            'watermark_id' => Arr::get($input, 'watermark_id', $preset->watermark_id),
            'watermark_position' => Arr::get($input, 'watermark_position', $preset->watermark_position),
            'watermark_opacity' => Arr::get($input, 'watermark_opacity', $preset->watermark_opacity),
        ]);
    }
}
