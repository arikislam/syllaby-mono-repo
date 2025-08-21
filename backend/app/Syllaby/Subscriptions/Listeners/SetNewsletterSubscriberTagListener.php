<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Shared\Newsletters\Newsletter;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\Contracts\SubscriptionEventHook;

class SetNewsletterSubscriberTagListener implements ShouldQueue
{
    public function __construct(protected Newsletter $newsletter)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionEventHook $event): void
    {
        $user = $event->user;
        $tags = $user->onTrial() ? ['trial'] : ['customer'];

        $this->newsletter->withTags($tags)->update($user);
    }
}
