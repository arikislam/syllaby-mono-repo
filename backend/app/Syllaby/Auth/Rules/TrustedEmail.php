<?php

namespace App\Syllaby\Auth\Rules;

use Closure;
use Illuminate\Support\Str;
use App\Syllaby\Loggers\Suppression;
use Illuminate\Contracts\Validation\ValidationRule;
use Beeyev\DisposableEmailFilter\DisposableEmailFilter;

class TrustedEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $email, Closure $fail): void
    {
        $filter = new DisposableEmailFilter;

        if (! $filter->isDisposableEmailAddress($email)) {
            return;
        }

        Suppression::updateOrCreate(['email' => $email], [
            'reason' => 'Disposable Email',
            'bounce_type' => 'Permanent',
            'trace_id' => Str::uuid(),
            'bounced_at' => now(),
        ]);
    }
}
