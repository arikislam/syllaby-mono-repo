<?php

namespace App\Syllaby\Subscriptions\Events;

use App\Syllaby\Users\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Syllaby\Subscriptions\Contracts\SubscriptionEventHook;

class PaymentFailed implements SubscriptionEventHook
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user, public array $payload) {}
}
