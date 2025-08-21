<?php

namespace App\Http\Requests\Presets;

use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Enums\Sfx;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Videos\Enums\Transition;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Videos\Enums\WatermarkPosition;

class ManageFacelessPresetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'volume' => ['sometimes', 'nullable', 'string', 'max:50'],
            'language' => ['sometimes', 'nullable', 'string', 'max:50'],
            'font_color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'font_family' => ['sometimes', 'nullable', 'string', 'max:50'],
            'duration' => ['sometimes', 'nullable', 'integer'],
            'orientation' => ['sometimes', 'nullable', 'string', 'max:26', Rule::in(Dimension::values())],
            'position' => ['nullable', 'string', 'max:50'],
            'caption_animation' => ['nullable', 'string', 'max:50'],
            'sfx' => ['sometimes', 'nullable', 'string', 'max:50', Rule::in(Sfx::values())],
            'music_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')],
            'music_category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('tags', 'id')],
            'background_id' => ['sometimes', 'nullable', 'integer', Rule::exists('assets', 'id')],
            'genre_id' => ['sometimes', 'nullable', 'integer', Rule::exists('genres', 'id')],
            'voice_id' => ['sometimes', 'nullable', 'integer', Rule::exists('voices', 'id')],
            'transition' => ['sometimes', 'nullable', 'string', 'max:50', Rule::in(Transition::values())],
            'overlay' => ['sometimes', 'nullable', 'string', 'max:50', Rule::in(Overlay::values())],
            'watermark_id' => ['sometimes', 'nullable', 'integer', Rule::exists('assets', 'id')->where('user_id', $this->user()->id)],
            'watermark_position' => ['sometimes', 'nullable', 'string', Rule::in(WatermarkPosition::values())],
            'watermark_opacity' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'resource_id' => ['sometimes', 'nullable', 'integer', Rule::exists('resources', 'id')],
        ];
    }
}
