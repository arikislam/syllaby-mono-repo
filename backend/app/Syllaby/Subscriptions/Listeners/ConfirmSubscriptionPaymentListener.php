<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Syllaby\Subscriptions\Events\SubscriptionCreated;
use App\Syllaby\Subscriptions\Notifications\SubscriptionConfirmation;

class ConfirmSubscriptionPaymentListener
{
    /**
     * Handle the event.
     */
    public function handle(SubscriptionCreated $event): void
    {
        $event->user->notify(new SubscriptionConfirmation);
    }
}
