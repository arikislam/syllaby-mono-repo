<?php

namespace App\Syllaby\Characters\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\Syllaby\Characters\Character;

class CharacterGenerationFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public Character $character) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'character-generation-failed';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'character_id' => $this->character->id,
            'character_name' => $this->character->name,
            'style' => $this->character->genre?->slug,
            'message' => 'Unfortunately, your custom character generation failed. Credits have been refunded.',
        ];
    }
}
