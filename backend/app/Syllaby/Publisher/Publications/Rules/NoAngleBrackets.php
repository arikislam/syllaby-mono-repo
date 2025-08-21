<?php

namespace App\Syllaby\Publisher\Publications\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoAngleBrackets implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/[<>]/', $value)) {
            $fail('The :attribute cannot contain angle brackets.');
        }
    }
}
