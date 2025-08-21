<?php

namespace App\Http\Requests\Generators;

use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class GenerateScriptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $action = CreditEventEnum::CONTENT_PROMPT_REQUESTED;

        return $gate->inspect('generate', [Generator::class, $action]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'length' => ['required', 'integer'],
            'style' => ['required', 'string', 'max:255'],
            'tone' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
        ];
    }
}
