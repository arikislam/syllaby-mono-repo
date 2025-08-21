<?php

namespace App\Syllaby\Auth\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Auth\Authenticatable;

class UserAuthenticated
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $guard, public Authenticatable $user, public bool $remember) {}
}
