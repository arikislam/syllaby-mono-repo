<?php

namespace App\Http\Requests\RealClones;

use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class GenerateRealCloneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $action = CreditEventEnum::REAL_CLONE_AND_TEXT_TO_SPEECH;
        $response = $gate->inspect('generate', [Generator::class, $action]);

        return match (true) {
            $response->denied() => $response,
            default => $gate->inspect('generate', $this->route('clone')),
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
