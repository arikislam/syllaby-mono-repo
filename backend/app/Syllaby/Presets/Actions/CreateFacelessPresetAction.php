<?php

namespace App\Syllaby\Presets\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Presets\FacelessPreset;

class CreateFacelessPresetAction
{
    /**
     * Create a new faceless preset.
     */
    public function handle(User $user, array $input): FacelessPreset
    {
        return FacelessPreset::create([
            'user_id' => $user->id,
            'voice_id' => Arr::get($input, 'voice_id'),
            'music_id' => Arr::get($input, 'music_id'),
            'music_category_id' => Arr::get($input, 'music_category_id'),
            'resource_id' => Arr::get($input, 'resource_id'),
            'genre_id' => Arr::get($input, 'genre_id'),
            'background_id' => Arr::get($input, 'background_id'),
            'name' => Arr::get($input, 'name', 'Default'),
            'language' => Arr::get($input, 'language'),
            'font_family' => Arr::get($input, 'font_family'),
            'font_color' => Arr::get($input, 'font_color'),
            'position' => Arr::get($input, 'position'),
            'caption_animation' => Arr::get($input, 'caption_animation'),
            'duration' => Arr::get($input, 'duration'),
            'orientation' => Arr::get($input, 'orientation'),
            'transition' => Arr::get($input, 'transition'),
            'volume' => Arr::get($input, 'volume'),
            'sfx' => Arr::get($input, 'sfx'),
            'overlay' => Arr::get($input, 'overlay'),
            'watermark_id' => Arr::get($input, 'watermark_id'),
            'watermark_position' => Arr::get($input, 'watermark_position'),
            'watermark_opacity' => Arr::get($input, 'watermark_opacity'),
        ]);
    }
}
