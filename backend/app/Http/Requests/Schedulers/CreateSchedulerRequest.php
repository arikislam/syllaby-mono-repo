<?php

namespace App\Http\Requests\Schedulers;

use RRule\RRule;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateSchedulerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'topic' => ['required_without:csv', 'string', 'max:255'],
            'social_channels' => ['required', 'array'],
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
            'days' => ['required', 'integer', 'min:1', 'max:30'],
            'character_id' => ['sometimes', 'nullable', 'integer', Rule::exists('characters', 'id')],

            'hours' => ['required', 'array', "size:{$this->input('times_per_day')}"],
            'hours.*' => ['required', 'date_format:H:i'],

            'times_per_day' => ['required', 'integer', 'min:1', 'max:7'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'weekdays' => ['required', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['required', 'string', Rule::in(array_keys(RRule::WEEKDAYS))],

            'ai_labels' => ['sometimes', 'boolean'],
            'custom_description' => ['sometimes', 'nullable', 'string', 'max:255'],

            'csv' => ['required_without:topic', 'array', "size:{$this->csvSize()}"],
            'csv.*.script' => ['required', 'string'],
            'csv.*.title' => ['required', 'string', 'max:255'],
            'csv.*.caption' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'hours.*.date_format' => 'Hours must be in the format HH:MM.',
            'hours.size' => 'You must specify a time for each scheduled publication.',
            'social_channels.required' => 'Connect/Select at least one social media account.',
            'weekdays.required' => 'Select at least one day of the week.',
            'weekdays.*.in' => 'Invalid weekday code. Use: SU, MO, TU, WE, TH, FR, SA',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'csv.size' => "The number of scripts is not matching with the number of planned posts."
        ];
    }

    /**
     * Prepares the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'topic' => $this->input('topic', sprintf('%s - %s', 'Bulk Scheduler', now()->format('Y-m-d H:i:s'))),
        ]);
    }

    private function csvSize(): int
    {
        return $this->input('times_per_day') * $this->input('days');
    }
}
