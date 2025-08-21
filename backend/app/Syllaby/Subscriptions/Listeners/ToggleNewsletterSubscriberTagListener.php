<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Arr;
use App\Shared\Newsletters\Newsletter;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\Events\SubscriptionUpdated;

class ToggleNewsletterSubscriberTagListener implements ShouldQueue
{
    public function __construct(protected Newsletter $newsletter)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionUpdated $event): void
    {
        $user = $event->user;
        $payload = $event->payload;

        if (!$this->wantsToResume($payload)) {
            return;
        }

        $tags = $user->onTrial() ? ['trial'] : ['paid'];
        $this->newsletter->withTags($tags)->update($user);
    }

    /**
     * Checks if the user wants to resume the subscriptions.
     */
    private function wantsToResume(array $payload): bool
    {
        $currentCancelEnd = Arr::get($payload, 'data.object.cancel_at_period_end', false);
        $previousCancelEnd = Arr::get($payload, 'data.previous_attributes.cancel_at_period_end', false);

        return $currentCancelEnd === false && $previousCancelEnd === true;
    }
}
