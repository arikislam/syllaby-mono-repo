<?php

namespace App\Http\Requests\Authentication;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $this->merge([
            'provider' => $this->route('provider'),
        ]);
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in('google')]
        ];
    }
}
