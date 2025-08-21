<?php

namespace App\Http\Requests\Schedulers;

use RRule\RRule;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchedulerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('update', $this->route('scheduler'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'topic' => ['sometimes', 'string', 'max:255'],
            'social_channels' => ['required', 'array'],
            'days' => ['required', 'integer', 'min:1', 'max:30'],
            'character_id' => ['sometimes', 'nullable', 'integer', Rule::exists('characters', 'id')],

            'times_per_day' => ['required', 'integer', 'min:1', 'max:5'],
            'hours' => ['required', 'array', "size:{$this->input('times_per_day')}"],
            'hours.*' => ['required', 'date_format:H:i'],

            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'weekdays' => ['required', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['required', 'string', Rule::in(array_keys(RRule::WEEKDAYS))],

            'ai_labels' => ['sometimes', 'boolean'],
            'custom_description' => ['sometimes', 'nullable', 'string', 'max:255'],

            'csv' => ['sometimes', 'array', "size:{$this->csvSize()}"],
            'csv.*.script' => ['required', 'string'],
            'csv.*.title' => ['required', 'string', 'max:255'],
            'csv.*.caption' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    private function csvSize(): int
    {
        return $this->input('times_per_day') * $this->input('days');
    }
}
