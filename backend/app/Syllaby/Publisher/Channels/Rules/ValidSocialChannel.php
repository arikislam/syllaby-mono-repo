<?php

namespace App\Syllaby\Publisher\Channels\Rules;

use Closure;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class ValidSocialChannel implements ValidationRule
{
    public function __construct(protected ?string $provider, protected ?SocialChannel $channel = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->exists($value)) {
            $fail("The :attribute is either invalid or is not supported by {$this->provider}");
        }
    }

    private function exists(mixed $value): bool
    {
        $this->channel ??= SocialChannel::where('id', $value)->first();

        return $this->channel->where('id', $value)
            ->whereRelation('account', 'user_id', auth('sanctum')->id())
            ->when($this->provider, fn ($query) => $query->whereRelation('account', 'provider', SocialAccountEnum::fromString($this->provider)->value))
            ->exists();
    }
}
