<?php

namespace App\Syllaby\Characters\Listeners;

use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Characters\Events\CharacterGenerationFailed;
use App\Syllaby\Characters\Notifications\CharacterGenerationFailedNotification;

class RefundAndNotifyUser
{
    public function __construct(protected CreditService $credit) {}

    public function handle(CharacterGenerationFailed $event): void
    {
        $user = $event->character->user;

        $this->credit
            ->setUser($user)
            ->refund($event->character, CreditEventEnum::CUSTOM_CHARACTER_PURCHASED);

        $user->notify(new CharacterGenerationFailedNotification($event->character));
    }
}
