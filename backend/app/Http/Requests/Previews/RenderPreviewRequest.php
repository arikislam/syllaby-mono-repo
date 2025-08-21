<?php

namespace App\Http\Requests\Previews;

use App\Syllaby\Speeches\Voice;
use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Auth\Access\Response;
use Illuminate\Validation\Validator;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Videos\Enums\TextPosition;
use App\Syllaby\Videos\Enums\CaptionEffect;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

class RenderPreviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        if ($this->user()->subscribed()) {
            return Response::deny('Only new users can create a preview video.');
        }

        if ($this->user()->videos()->exists()) {
            return Response::deny('You are not authorized to create more than 1 videos.');
        }

        return Response::allow();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'voice_id' => ['bail', 'required', 'integer', Rule::exists('voices', 'id')],
            'music_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')],
            'genre_id' => ['bail', 'required', 'integer', Rule::exists('genres', 'id')],
            'transition' => ['sometimes', 'string', Rule::in(Transition::values())],
            'overlay' => ['sometimes', 'string', Rule::in(Overlay::values())],
            'sfx' => ['sometimes', 'nullable', 'string', Rule::in(Sfx::values())],
            'volume' => ['required_with:music_id', 'string', Rule::in('low', 'medium', 'high')],
            'script' => ['bail', 'required', 'string', 'min:20'],
            'duration' => ['required', 'integer', 'max:40'],
            'aspect_ratio' => ['required', 'string', Rule::in('16:9', '9:16', '1:1')],
            'captions' => ['nullable', 'array'],
            'captions.font_family' => ['sometimes', 'nullable', 'string', Rule::in(FontPresets::values())],
            'captions.font_color' => ['sometimes', 'string'],
            'captions.position' => ['sometimes', 'string', Rule::in(TextPosition::values())],
            'captions.effect' => ['sometimes', 'string', Rule::in(CaptionEffect::values())],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $voice = Voice::find($this->input('voice_id'));
            $readingTime = reading_time($this->string('script'), $voice->words_per_minute);

            if ($readingTime > 40) {
                $validator->errors()->add('script', 'The script is too long.');
            }
        }];
    }
}
