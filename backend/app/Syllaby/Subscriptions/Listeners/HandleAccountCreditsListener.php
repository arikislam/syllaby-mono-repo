<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Events\PaymentSucceeded;
use App\Syllaby\Subscriptions\Actions\RenewCreditsAction;

readonly class HandleAccountCreditsListener
{
    /**
     * Create the event listener.
     */
    public function __construct(private RenewCreditsAction $renewer) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentSucceeded $event): void
    {
        $user = $event->user;
        $data = Arr::get($event->payload, 'data.object');

        match (Arr::get($data, 'billing_reason')) {
            'subscription_cycle' => $this->renewer->handle($user),
            default => null,
        };
    }
}
