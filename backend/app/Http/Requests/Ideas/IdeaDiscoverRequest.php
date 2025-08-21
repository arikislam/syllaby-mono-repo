<?php

namespace App\Http\Requests\Ideas;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Ideas\Enums\Networks;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class IdeaDiscoverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $action = CreditEventEnum::IDEA_DISCOVERED;

        return $gate->inspect('generate', [Generator::class, $action]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string', 'min:2', 'max:80'],
            'network' => ['required', 'string', Rule::in(Networks::toArray())],
        ];
    }
}
