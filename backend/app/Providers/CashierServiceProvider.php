<?php

namespace App\Providers;

use App\Syllaby\Users\User;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events as Hooks;
use App\Syllaby\Subscriptions\Events;
use App\Syllaby\Subscriptions\Listeners;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class CashierServiceProvider extends EventServiceProvider
{
    protected $listen = [
        Hooks\WebhookReceived::class => [
            Listeners\StripeHookReceivedListener::class,
        ],
        Hooks\WebhookHandled::class => [
            Listeners\StripeHookHandledListener::class,
        ],
        Events\CheckoutCompleted::class => [
            Listeners\RecordStripeLogsListener::class,
            Listeners\HandlePurchaseListener::class,
        ],
        Events\PaymentSucceeded::class => [
            Listeners\RecordStripeLogsListener::class,
            Listeners\HandleAccountCreditsListener::class,
        ],
        Events\PaymentFailed::class => [
            Listeners\RecordStripeLogsListener::class,
            Listeners\NotifyUserPaymentFailedListener::class,
        ],
        Events\SubscriptionCreated::class => [
            Listeners\GenerateWelcomeEmail::class,
            Listeners\RecordStripeLogsListener::class,
            Listeners\SetSubscriptionCreditsListener::class,
            Listeners\SetNewsletterSubscriberTagListener::class,
            Listeners\ConfirmSubscriptionPaymentListener::class,
            Listeners\TrackTrialStartedListener::class,
        ],
        Events\TrialWillEnd::class => [
            Listeners\NotifyUserTrialWillEndListener::class,
        ],
        Events\InvoicePaymentActionRequired::class => [
            Listeners\RecordStripeLogsListener::class,
            Listeners\NotifyPaymentActionRequiredListener::class,
        ],
        Events\SubscriptionResumed::class => [
            Listeners\RecordStripeLogsListener::class,
            Listeners\SetNewsletterSubscriberTagListener::class,
        ],
        Events\SubscriptionUpdated::class => [
            Listeners\RecordStripeLogsListener::class,
            Listeners\HandlePlanFeaturesListener::class,
            Listeners\NotifySubscriptionCancelledListener::class,
            Listeners\ToggleNewsletterSubscriberTagListener::class,
            Listeners\TrackSubscriptionStartedListener::class,
        ],
        Events\SubscriptionDeleted::class => [
            Listeners\RecordStripeLogsListener::class,
            Listeners\SetNewsletterCancelledTagListener::class,
            Listeners\NotifySubscriptionDeletedListener::class,
        ],
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(User::class);
    }
}
