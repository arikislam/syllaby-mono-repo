<?php

namespace App\Syllaby\Auth\Helpers;

use Illuminate\Validation\Rules\Password;

class Utility
{
    public static function setPasswordRules(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
    }
}
