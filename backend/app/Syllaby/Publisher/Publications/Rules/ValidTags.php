<?php

namespace App\Syllaby\Publisher\Publications\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTags implements ValidationRule
{
    const int MAX_TAG_LIMIT = 500;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $length = collect($value)->sum(fn ($tag) => mb_strlen($tag));

        if ($length > self::MAX_TAG_LIMIT) {
            $fail('Total tag length exceeds 500 characters. Please shorten your tags');
        }
    }
}
