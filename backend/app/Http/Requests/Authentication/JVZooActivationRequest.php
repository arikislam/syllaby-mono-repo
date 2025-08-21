<?php

namespace App\Http\Requests\Authentication;

use App\Syllaby\Auth\Helpers\Utility;
use Illuminate\Foundation\Http\FormRequest;

class JVZooActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'max:48', Utility::setPasswordRules()],
        ];
    }
}
