<?php

namespace App\Http\Requests\Assets;

use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class CreateThumbnailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $action = CreditEventEnum::SINGLE_AI_IMAGE_GENERATED;

        return $gate->inspect('generate', [Generator::class, $action, 1]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'context' => ['required', 'string', 'max:2500'],
            'text' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'required_with:text', 'string'],
            'amount' => ['sometimes', 'integer', 'min:1', 'max:6'],
        ];
    }
}
