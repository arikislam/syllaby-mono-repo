<?php

namespace App\Http\Requests\Schedulers;

use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class OccurrenceScriptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $action = CreditEventEnum::CONTENT_PROMPT_REQUESTED;
        $credits = $gate->inspect('generate', [Generator::class, $action]);

        return match (true) {
            $credits->denied() => $credits,
            default => $gate->inspect('update', $this->route('occurrence')),
        };
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}
