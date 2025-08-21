<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Stripe\Stripe;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use App\Syllaby\Subscriptions\CardFingerprint;
use App\Syllaby\Subscriptions\Events\TrialWillEnd;
use App\Syllaby\Subscriptions\Events\PaymentFailed;
use App\Syllaby\Subscriptions\Events\PaymentSucceeded;
use App\Syllaby\Subscriptions\Events\CheckoutCompleted;
use App\Syllaby\Subscriptions\Events\SubscriptionResumed;
use App\Syllaby\Subscriptions\Enums\StripeEvent as StripeEvent;

class StripeHookReceivedListener
{
    /**
     * Event Type
     */
    protected string $event;

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $hook): void
    {
        $payload = $hook->payload;
        $this->event = $hook->payload['type'];

        Stripe::setMaxNetworkRetries(3);

        match ($this->event) {
            StripeEvent::CHECKOUT_SESSION_COMPLETED->value => $this->handleCheckoutCompleted($payload),
            StripeEvent::INVOICE_PAYMENT_SUCCEEDED->value => $this->handleInvoicePaymentSuccess($payload),
            StripeEvent::INVOICE_PAYMENT_FAILED->value => $this->handleInvoicePaymentFailed($payload),
            StripeEvent::CUSTOMER_SUBSCRIPTION_TRIAL_WILL_END->value => $this->handleTrialWillEnd($payload),
            StripeEvent::CUSTOMER_SUBSCRIPTION_RESUMED->value => $this->handleSubscriptionResumed($payload),
            StripeEvent::PAYMENT_METHOD_ATTACHED->value => $this->handleAttachedPaymentMethod($payload),
            default => null
        };
    }

    protected function handleTrialWillEnd(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new TrialWillEnd($user, $payload));
    }

    /**
     * Set the stripe customer id on the user.
     */
    protected function handleCheckoutCompleted(array $payload): void
    {
        $checkout = Arr::get($payload, 'data.object');

        if (! $user = User::find($checkout['client_reference_id'])) {
            return;
        }

        event(new CheckoutCompleted($user, $payload));
    }

    /**
     * Sets credits to the user according to the subscription plan.
     */
    protected function handleInvoicePaymentSuccess(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        /** @var User $user */
        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new PaymentSucceeded($user, $payload));
    }

    /**
     * Handle subscriptions payment failures
     */
    protected function handleInvoicePaymentFailed(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        /** @var User $user */
        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new PaymentFailed($user, $payload));
    }

    /**
     * Handle customer subscription resumed.
     */
    protected function handleSubscriptionResumed(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        /** @var User $user */
        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        event(new SubscriptionResumed($user, $payload));
    }

    /**
     * Handle attached payment method.
     */
    private function handleAttachedPaymentMethod(array $payload): void
    {
        $stripeId = Arr::get($payload, 'data.object.customer');

        /** @var User $user */
        if (! $user = Cashier::findBillable($stripeId)) {
            return;
        }

        if (! $fingerprint = Arr::get($payload, 'data.object.card.fingerprint')) {
            return;
        }

        CardFingerprint::updateOrCreate(
            attributes: ['user_id' => $user->id, 'fingerprint' => $fingerprint],
            values: ['updated_at' => now()]
        );
    }
}
