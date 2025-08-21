<?php

namespace App\Http\Requests\Schedulers;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Videos\Enums\TextPosition;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Videos\Enums\CaptionEffect;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use Illuminate\Contracts\Validation\Validator;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

class RunSchedulerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $action = CreditEventEnum::FACELESS_VIDEO_GENERATED;
        $input = ['duration' => $this->input('options.duration', 60)];

        return $gate->inspect('run', [$this->route('scheduler'), $action, $input]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $languages = array_keys(config('generators.options.languages'));
        $durations = Arr::pluck(config('generators.options.faceless-durations'), 'value');

        return [
            'destination_id' => ['sometimes', 'integer', Rule::exists('resources', 'id')],

            'options' => ['required', 'array'],
            'options.duration' => ['required', 'integer', Rule::in($durations)],
            'options.voice_id' => ['required', 'integer', Rule::exists('voices', 'id')],
            'options.language' => ['required', 'string', 'max:255', Rule::in($languages)],
            'options.sfx' => ['sometimes', 'nullable', 'string',  Rule::in(Sfx::values())],
            'options.aspect_ratio' => ['required', 'string', Rule::in('16:9', '9:16', '1:1')],
            'options.type' => ['required', 'string', Rule::in(FacelessType::values())],
            'options.music_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')],
            'options.genre_id' => ['sometimes', 'nullable', 'integer', Rule::exists('genres', 'id')],
            'options.transition' => ['required_with:options.genre', 'string', Rule::in(Transition::values())],
            'options.background_id' => ['sometimes', 'nullable', 'integer', Rule::exists('assets', 'id')],
            'options.music_volume' => ['required_with:options.music_id', 'string', Rule::in('low', 'medium', 'high')],
            'options.overlay' => ['sometimes', 'nullable', 'string', Rule::in(Overlay::values())],
            'options.character_id' => ['sometimes', 'nullable', 'integer', Rule::exists('characters', 'id')],

            'captions' => ['required', 'array'],
            'captions.font_color' => ['sometimes', 'nullable', 'string'],
            'captions.font_family' => ['required', 'string', Rule::in(FontPresets::values())],
            'captions.position' => ['sometimes', 'nullable', 'string',  Rule::in(TextPosition::values())],
            'captions.effect' => ['sometimes', 'nullable', 'string', Rule::in(CaptionEffect::values())],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasEmptyScripts($this->route('scheduler'))) {
                $validator->errors()->add('scripts', 'All occurrences must have scripts before running the scheduler.');
            }
        });
    }

    /**
     * Check if the scheduler has occurrences with empty scripts.
     */
    private function hasEmptyScripts(?Scheduler $scheduler = null): bool
    {
        return $scheduler?->occurrences()->whereNull('script')->orWhere('script', '')->exists();
    }
}
