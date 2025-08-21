<?php

namespace App\Syllaby\Clonables\Notifications;

use Arr;
use App\Syllaby\Users\User;
use Illuminate\Bus\Queueable;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\SlackAttachment;

class AvatarCloneRequested extends Notification
{
    use Queueable;

    public function __construct(protected Clonable $clonable, protected User $user)
    {
    }

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $url = Arr::get($this->clonable->metadata, 'url');
        $gender = Arr::get($this->clonable->metadata, 'gender') ?? 'Not Specified';

        return (new SlackMessage)
            ->from("Bot")
            ->content("🌟 *New Avatar Clone Request* 🌟")
            ->attachment(function (SlackAttachment $attachment) use ($url, $gender) {
                $attachment->fields([
                    'Name' => "{$this->user->name}",
                    'Email' => "{$this->user->email}",
                    'Clone ID' => "{$this->clonable->id}",
                    'Sample Video' => $url,
                    'Gender' => ucfirst($gender)
                ])->color('#36a64f');
            });
    }
}