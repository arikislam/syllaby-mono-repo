<?php

namespace App\Http\Requests\Videos;

use App\Syllaby\Videos\Video;
use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Videos\Enums\TextPosition;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Videos\Enums\CaptionEffect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Syllaby\Videos\Enums\WatermarkPosition;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

class RenderFacelessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $faceless = $this->route('faceless');

        $credits = $gate->inspect('faceless', [Generator::class, $faceless, $this->input('voice_id')]);

        return match (true) {
            $credits->denied() => $credits,
            default => $gate->inspect('render', [$faceless->video, Video::FACELESS]),
        };
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'voice_id' => ['nullable', 'integer', Rule::exists('voices', 'id')],
            'background_id' => ['sometimes', 'nullable', 'integer', Rule::exists('assets', 'id')],
            'music_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')],
            'genre_id' => ['sometimes', 'nullable', 'integer', Rule::exists('genres', 'id')],
            'type' => ['sometimes', 'string', Rule::in(FacelessType::values())],
            'transition' => ['required_with:genre', 'string', Rule::in(Transition::values())],
            'overlay' => ['sometimes', 'string', Rule::in(Overlay::values())],
            'sfx' => ['sometimes', 'nullable', 'string', Rule::in(Sfx::values())],
            'volume' => ['required_with:music_id', 'string', Rule::in('low', 'medium', 'high')],
            'script' => ['required', 'string'],
            'duration' => ['required', 'integer'],
            'aspect_ratio' => ['required', 'string', Rule::in(array_values(Dimension::ratios()))],
            'character_id' => ['sometimes', 'nullable', 'integer', Rule::exists('characters', 'id')],

            'captions' => ['nullable', 'array'],
            'captions.font_family' => ['sometimes', 'nullable', 'string', Rule::in(FontPresets::values())],
            'captions.font_color' => ['sometimes', 'nullable', 'string'],
            'captions.position' => ['sometimes', 'string', Rule::in(TextPosition::values())],
            'captions.effect' => ['sometimes', 'string', Rule::in(CaptionEffect::values())],

            'destination_id' => ['sometimes', 'integer', Rule::exists('resources', 'id')],

            'watermark' => ['nullable', 'array'],
            'watermark.id' => ['sometimes', 'nullable', 'integer', Rule::exists('assets', 'id')->where('user_id', $this->user()->id)],
            'watermark.position' => ['sometimes', 'nullable', 'string', Rule::in(WatermarkPosition::values())],
            'watermark.opacity' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],

            'publications' => ['nullable', 'array'],
            'publications.*.channel_id' => ['integer', Rule::exists('social_channels', 'id')],
            'publications.*.scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],

            'ai_labels' => ['sometimes', 'boolean'],
            'custom_description' => ['sometimes', 'nullable', 'string', 'max:255'],

            'assets' => ['nullable', 'array'],
            'assets.*.id' => ['required', 'integer', Rule::exists('assets', 'id')],
            'assets.*.order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $validator->errors()->has('assets') && $this->has('assets') && is_array($this->input('assets'))) {
                $this->validateAssetOrderSequence($validator);
            }
        }];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'script.max' => 'Faceless videos are limited to 1000 characters. Please shorten your script.',
            'destination_id.exists' => 'The destination is not a valid folder.',
        ];
    }

    /**
     * Validate that the asset order sequence has no gaps.
     */
    protected function validateAssetOrderSequence(Validator $validator): void
    {
        $orders = collect($this->input('assets'))->pluck('order')->sort()->values()->toArray();

        $expected = range(0, count($orders) - 1);

        if ($orders !== $expected) {
            $validator->errors()->add('assets', 'The asset order sequence must not have any gaps. Orders should start from 0 and be consecutive.');
        }
    }
}
