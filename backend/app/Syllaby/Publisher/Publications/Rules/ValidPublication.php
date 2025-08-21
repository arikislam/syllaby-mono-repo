<?php

namespace App\Syllaby\Publisher\Publications\Rules;

use Closure;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\Vendors\Publisher;

class ValidPublication implements ValidationRule
{
    public function __construct(
        protected string $provider,
        protected Publication $publication,
        protected mixed $type = 'post'
    ) {
        $this->type = PostType::tryFrom($type);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->type) {
            $fail('The identifiers are either invalid or un-supported');

            return;
        }

        if (! $this->exists($value)) {
            $fail("The identifiers are either invalid or not supported by {$this->provider}");
        }
    }

    private function exists(mixed $value): bool
    {
        $publication = $this->publication->where('id', $value)
            ->where('user_id', auth('sanctum')->id())
            ->first();

        if (blank($publication?->asset())) {
            return false;
        }

        return Publisher::driver($this->provider)->valid($publication, $this->type);
    }
}
