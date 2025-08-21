<?php

namespace App\Providers;

use App\Syllaby\Subscriptions\Events\GooglePlayRtdnHandled;
use App\Syllaby\Subscriptions\Events\GooglePlayRtdnReceived;
use App\Syllaby\Subscriptions\Events\GooglePlayProductPurchased;
use App\Syllaby\Subscriptions\Listeners\GooglePlayGenerateWelcomeEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionRenewed;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionCanceled;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionPurchased;
use App\Syllaby\Subscriptions\Listeners\GooglePlayRtdnHandledListener;
use App\Syllaby\Subscriptions\Listeners\GooglePlayRtdnReceivedListener;
use App\Syllaby\Subscriptions\Listeners\GooglePlaySubscriptionCanceledListener;
use App\Syllaby\Subscriptions\Listeners\GooglePlaySetSubscriptionCreditsListener;
use App\Syllaby\Subscriptions\Listeners\GooglePlayTrackSubscriptionStartedListener;
use App\Syllaby\Subscriptions\Listeners\GooglePlayHandleProductPurchaseListener;

class GooglePlayRtdnServiceProvider extends EventServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Main RTDN event - handles initial processing
        GooglePlayRtdnReceived::class => [
            GooglePlayRtdnReceivedListener::class,
        ],

        // After RTDN is created and ready for verification
        GooglePlayRtdnHandled::class => [
            GooglePlayRtdnHandledListener::class,
        ],

        // Subscription purchased/renewed events
        GooglePlaySubscriptionPurchased::class => [
            GooglePlayGenerateWelcomeEmail::class,
            GooglePlaySetSubscriptionCreditsListener::class,
            GooglePlayTrackSubscriptionStartedListener::class,
            // TODO: Add newsletter subscription listener if needed
            // SetNewsletterSubscriberTagListener::class,
        ],

        GooglePlaySubscriptionRenewed::class => [
            GooglePlaySetSubscriptionCreditsListener::class,
            // TODO: Create Google Play specific renewal listeners if needed
        ],

        // Subscription cancellation events
        GooglePlaySubscriptionCanceled::class => [
            GooglePlaySubscriptionCanceledListener::class,
            // TODO: Add newsletter cancellation listener if needed
            // SetNewsletterCancelledTagListener::class,
        ],

        // Product purchase events
        GooglePlayProductPurchased::class => [
            GooglePlayHandleProductPurchaseListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
