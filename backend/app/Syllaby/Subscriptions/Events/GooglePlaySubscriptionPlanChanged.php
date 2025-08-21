<?php

namespace App\Syllaby\Subscriptions\Events;

use App\Syllaby\Users\User;
use Illuminate\Queue\SerializesModels;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GooglePlaySubscriptionPlanChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public GooglePlayRtdn $rtdn,
        public User $user,
        public ?string $previousPurchaseToken = null
    ) {}
}
