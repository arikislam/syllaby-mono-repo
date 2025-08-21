<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Syllaby\Users\User;
use Illuminate\Auth\Events\Registered;
use App\Shared\Newsletters\Newsletter;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscribeNewsletterListener implements ShouldQueue
{
    public function __construct(protected Newsletter $newsletter)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->newsletter->withTags(['non-user'])->subscribe($user, [
            'fields' => [
                'name' => $user->name,
                'syllaby_id' => $user->getAuthIdentifier(),
            ],
        ]);
    }
}
