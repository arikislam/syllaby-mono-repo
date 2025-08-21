<?php

namespace App\Syllaby\Subscriptions\Events;

use App\Syllaby\Users\User;
use Illuminate\Queue\SerializesModels;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GooglePlaySubscriptionCanceled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The Google Play RTDN model instance.
     */
    public GooglePlayRtdn $rtdn;

    /**
     * The user instance.
     */
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(GooglePlayRtdn $rtdn, User $user)
    {
        $this->rtdn = $rtdn;
        $this->user = $user;
    }
}
