<?php

namespace App\Http\Requests\Videos;

use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Enums\Sfx;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Videos\Enums\TextPosition;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

class UpdateFacelessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        $video = $this->route('faceless')->video;

        return $gate->allows('update', $video);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'voice_id' => ['sometimes', 'integer', Rule::exists('voices', 'id')],
            'background_id' => ['sometimes', 'integer', Rule::exists('assets', 'id')],
            'music_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')],
            'genre_id' => ['sometimes', 'integer', Rule::exists('genres', 'id')],
            'transition' => ['sometimes', 'string', Rule::in(Transition::values())],
            'sfx' => ['sometimes', 'nullable', 'string', Rule::in(Sfx::values())],
            'volume' => ['sometimes', 'nullable', 'string', Rule::in('low', 'medium', 'high')],
            'type' => ['sometimes', 'string', Rule::in(FacelessType::values())],
            'script' => ['sometimes', 'string'],
            'duration' => ['sometimes', 'integer'],
            'aspect_ratio' => ['sometimes', 'string', Rule::in('16:9', '9:16', '1:1')],
            'captions' => ['nullable', 'array'],
            'captions.font_family' => ['sometimes', 'string', Rule::in(FontPresets::values())],
            'captions.font_color' => ['sometimes', 'string'],
            'captions.position' => ['sometimes', 'string', Rule::in(TextPosition::values())],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'script.max' => 'Faceless videos are limited to 950 characters. Please shorten your script.',
        ];
    }
}
