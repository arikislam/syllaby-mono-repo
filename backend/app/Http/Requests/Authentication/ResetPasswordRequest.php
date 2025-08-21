<?php

namespace App\Http\Requests\Authentication;

use App\Syllaby\Auth\Helpers\Utility;
use Illuminate\Foundation\Http\FormRequest;

/** @property string $email */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'max:48', Utility::setPasswordRules()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => __('passwords.user'),
        ];
    }
}
