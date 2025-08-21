<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Syllaby\Subscriptions\Events\SubscriptionDeleted;
use App\Syllaby\Subscriptions\Notifications\SubscriptionTermination;

class NotifySubscriptionDeletedListener
{
    /**
     * Handle the event.
     */
    public function handle(SubscriptionDeleted $event): void
    {
        $event->user->notify(new SubscriptionTermination);
    }
}
