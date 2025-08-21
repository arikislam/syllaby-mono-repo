<?php

namespace App\Http\Requests\User;

use App\Syllaby\Auth\Helpers\Utility;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'max:48', 'confirmed', 'different:current_password', Utility::setPasswordRules()],
        ];
    }
}
