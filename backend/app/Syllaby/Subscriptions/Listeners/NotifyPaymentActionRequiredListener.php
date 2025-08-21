<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Syllaby\Subscriptions\Notifications\ConfirmPaymentIntent;
use App\Syllaby\Subscriptions\Events\InvoicePaymentActionRequired;

class NotifyPaymentActionRequiredListener
{
    public function __construct() {}

    public function handle(InvoicePaymentActionRequired $event): void
    {
        $user = $event->user;
        $payload = $event->payload;

        $user->notify(new ConfirmPaymentIntent($payload));
    }
}
