<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Events\SubscriptionUpdated;
use App\Syllaby\Subscriptions\Notifications\SubscriptionCancellation;

class NotifySubscriptionCancelledListener
{
    /**
     * Handle the event.
     */
    public function handle(SubscriptionUpdated $event): void
    {
        $user = $event->user;

        if (! $this->requestedCancellation($event->payload)) {
            return;
        }

        if ($user->subscription()->ended()) {
            return;
        }

        $user->notify(new SubscriptionCancellation);
    }

    /**
     * Check if the user requested subscription to be cancelled.
     */
    private function requestedCancellation(array $payload): bool
    {
        return Arr::get($payload, 'data.object.cancel_at_period_end', false) === true;
    }
}
