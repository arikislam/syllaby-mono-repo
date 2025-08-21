<?php

namespace App\Shared\Newsletters;

use App\Syllaby\Users\User;

interface Newsletter
{
    /**
     * Checks whether the user gave permission to be added to a newsletter.
     */
    public function authorize(User $user): bool;

    /**
     * Subscribes a given email.
     */
    public function subscribe(User $user, array $data): bool;

    /**
     * Unsubscribes a given email.
     */
    public function unsubscribe(User $user): bool;

    /**
     * Update an existing subscriber.
     */
    public function update(User $user, array $data = []): bool;

    /**
     * Fetch a subscriber with the given email if exists.
     */
    public function subscriber(User $user): array;

    /**
     * Groups to be added with the subscriber.
     */
    public function withTags(array $tags): self;
}
