<?php

namespace App\Http\Requests\Videos;

use App\Syllaby\Videos\Video;
use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Videos\Enums\TextPosition;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Videos\Enums\CaptionEffect;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Videos\Enums\WatermarkPosition;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

class ExportFacelessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $faceless = $this->route('faceless');

        return $gate->inspect('export', [$faceless, Video::EDITED_FACELESS]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'transcriptions' => ['sometimes', 'array'],
            'transcriptions.*' => ['required', 'array'],
            'transcriptions.*.*' => ['required', 'string'],

            'captions' => ['nullable', 'array'],
            'captions.font_family' => ['sometimes', 'nullable', 'string', Rule::in(FontPresets::values())],
            'captions.font_color' => ['sometimes', 'nullable', 'string'],
            'captions.position' => ['sometimes', 'string', Rule::in(TextPosition::values())],
            'captions.effect' => ['sometimes', 'string', Rule::in(CaptionEffect::values())],

            'watermark' => ['nullable', 'array'],
            'watermark.id' => ['sometimes', 'nullable', 'integer', Rule::exists('assets', 'id')->where('user_id', $this->user()->id)],
            'watermark.position' => ['sometimes', 'nullable', 'string', Rule::in(WatermarkPosition::values())],
            'watermark.opacity' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],

            'transition' => ['required_with:genre', 'string', Rule::in(Transition::values())],
            'overlay' => ['sometimes', 'string', Rule::in(Overlay::values())],
            'sfx' => ['sometimes', 'nullable', 'string', Rule::in(Sfx::values())],
            'volume' => ['required_with:music_id', 'string', Rule::in('low', 'medium', 'high')],
            'music_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')],
        ];
    }
}
