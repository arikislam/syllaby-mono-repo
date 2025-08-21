<?php

namespace App\Syllaby\Credits\Listeners;

use App\Syllaby\Credits\Events\CreditsRefunded;
use App\Syllaby\Credits\Notifications\CreditsRefundedNotification;

class SendCreditsRefundNotification
{
    public function handle(CreditsRefunded $event): void
    {
        $history = $event->history;

        $history->user->notify(new CreditsRefundedNotification($history));
    }
}
