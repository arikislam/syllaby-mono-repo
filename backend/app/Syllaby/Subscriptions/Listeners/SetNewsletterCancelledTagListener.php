<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Shared\Newsletters\Newsletter;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\Events\SubscriptionDeleted;

class SetNewsletterCancelledTagListener implements ShouldQueue
{
    public function __construct(protected Newsletter $newsletter)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionDeleted $event): void
    {
        $user = $event->user;
        $tags = $user->onTrial() ? ['trial-canceled'] : ['canceled'];

        $this->newsletter->withTags($tags)->update(($user));
    }
}
