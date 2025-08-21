<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Events\PaymentFailed;
use App\Syllaby\Subscriptions\Notifications\InvoicePaymentFailed;

class NotifyUserPaymentFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        $user = $event->user;
        $invoice = Arr::get($event->payload, 'data.object');

        if (Arr::get($invoice, 'next_payment_attempt') === null) {
            return;
        }

        $user->notify(new InvoicePaymentFailed($invoice));
    }
}
