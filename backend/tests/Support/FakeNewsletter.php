<?php

namespace Tests\Support;

use App\Syllaby\Users\User;
use App\Shared\Newsletters\Newsletter;

class FakeNewsletter implements Newsletter
{
    public function authorize(User $user): bool
    {
        return true;
    }

    public function subscribe(User $user, array $data): bool
    {
        return true;
    }

    public function subscriber(User $user): array
    {
        return [];
    }

    public function unsubscribe(User $user): bool
    {
        return true;
    }

    public function update(User $user, array $data = []): bool
    {
        return true;
    }

    public function withTags(array $tags): self
    {
        return $this;
    }
}
