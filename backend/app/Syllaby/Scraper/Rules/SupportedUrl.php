<?php

namespace App\Syllaby\Scraper\Rules;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SupportedUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = parse_url($value, PHP_URL_HOST);

        if (Str::contains($host, ['youtube', 'facebook', 'twitter'])) {
            $fail('Social media platforms are not supported yet! Stay tuned as we are working on it.');
        }
    }
}
