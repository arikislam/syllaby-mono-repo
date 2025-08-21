<?php

namespace App\Shared\Slack\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class SubscriptionAlert extends Notification
{
    use Queueable;

    public function __construct(public array $users)
    {
    }

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->from("Jarvis")
            ->content("The following users have completed 3 months of subscription: \n\n" . collect($this->users)->map(fn ($name, $email) => "- *$name* ($email)")->implode("\n"));
    }
}
