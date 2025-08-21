<?php

namespace App\Syllaby\Publisher\Publications\Rules;

use Closure;
use Carbon\Carbon;
use Laravel\Pennant\Feature;
use Illuminate\Contracts\Validation\ValidationRule;

class EnsureValidWeeksRange implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $weeks = Feature::value('max_scheduled_weeks');

        if ($weeks === '*') {
            return;
        }

        $start = Carbon::now();
        $end = $start->copy()->addWeeks((int) $weeks);

        if (! Carbon::parse($value)->between($start, $end)) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return "Your plan doesn't allow to schedule publications with this much advance.";
    }
}
