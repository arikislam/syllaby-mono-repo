<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Arr;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use App\Syllaby\Subscriptions\Events\SubscriptionCreated;
use App\Syllaby\Subscriptions\Events\SubscriptionDeleted;
use App\Syllaby\Subscriptions\Events\SubscriptionUpdated;
use App\Syllaby\Subscriptions\Enums\StripeEvent as StripeEvent;
use App\Syllaby\Subscriptions\Events\InvoicePaymentActionRequired;

class StripeHookHandledListener
{
    /**
     * Event Type
     */
    protected string $event;

    /**
     * Handle the event.
     */
    public function handle(WebhookHandled $hook): void
    {
        $payload = $hook->payload;
        $this->event = $hook->payload['type'];

        match ($this->event) {
            StripeEvent::CUSTOMER_SUBSCRIPTION_CREATED->value => $this->handleSubscriptionCreated($payload),
            StripeEvent::CUSTOMER_SUBSCRIPTION_UPDATED->value => $this->handleSubscriptionUpdated($payload),
            StripeEvent::CUSTOMER_SUBSCRIPTION_DELETED->value => $this->handleSubscriptionDeleted($payload),
            StripeEvent::INVOICE_PAYMENT_ACTION_REQUIRED->value => $this->handleInvoicePaymentActionRequired($payload),
            default => null
        };
    }

    /**
     * Adds the default credits to the user according to the subscription plan.
     */
    protected function handleSubscriptionCreated(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new SubscriptionCreated($user, $payload));
    }

    /**
     * Set the warning subscription message
     */
    protected function handleSubscriptionUpdated(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new SubscriptionUpdated($user, $payload));
    }

    /**
     * Handles customer subscription deleted.
     */
    protected function handleSubscriptionDeleted(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new SubscriptionDeleted($user, $payload));
    }

    protected function handleInvoicePaymentActionRequired(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new InvoicePaymentActionRequired($user, $payload));
    }
}
