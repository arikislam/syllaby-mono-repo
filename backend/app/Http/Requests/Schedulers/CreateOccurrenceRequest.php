<?php

namespace App\Http\Requests\Schedulers;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class CreateOccurrenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $scheduler = $this->route('scheduler');

        $action = CreditEventEnum::CONTENT_PROMPT_REQUESTED;
        $credits = $gate->inspect('generate', [Generator::class, $action, $this->amount($scheduler)]);

        return match (true) {
            $credits->denied() => $credits,
            default => $gate->inspect('update', $scheduler),
        };
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $languages = array_keys(config('generators.options.languages'));
        $durations = Arr::pluck(config('generators.options.faceless-durations'), 'value');

        return [
            'topic' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'integer', Rule::in($durations)],
            'language' => ['required', 'string', 'max:255',  Rule::in($languages)],
        ];
    }

    /**
     * Maximum number of credits to be used by the scheduler for video script generation.
     */
    private function amount(Scheduler $scheduler): int
    {
        // Total number of videos to be generated
        $total = count($scheduler->rdates());

        // Assuming 1 credit per 1 minute of video script
        $amount = $total * ($this->integer('duration') / 60);

        return round($amount);
    }
}
