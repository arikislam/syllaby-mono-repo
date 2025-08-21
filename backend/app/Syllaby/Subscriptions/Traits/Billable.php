<?php

namespace App\Syllaby\Subscriptions\Traits;

use Laravel\Cashier\Concerns\HandlesTaxes;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use Laravel\Cashier\Concerns\ManagesCustomer;
use Laravel\Cashier\Concerns\ManagesInvoices;
use Laravel\Cashier\Concerns\PerformsCharges;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\GooglePlayPurchase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Concerns\ManagesPaymentMethods;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;

trait Billable
{
    use HandlesTaxes;
    use ManagesCustomer;
    use ManagesInvoices;
    use ManagesPaymentMethods;
    use ManagesSubscriptions;
    use PerformsCharges;

    /**
     * Check if the user uses Stripe for subscriptions.
     */
    public function usesStripe(): bool
    {
        return $this->subscription_provider === SubscriptionProvider::STRIPE;
    }

    /**
     * Check if the user uses Google Play for subscriptions.
     */
    public function usesGooglePlay(): bool
    {
        return $this->subscription_provider === SubscriptionProvider::GOOGLE_PLAY;
    }

    /**
     * Check if the user uses JVZoo for subscriptions.
     */
    public function usesJVZoo(): bool
    {
        return $this->subscription_provider === SubscriptionProvider::JVZOO;
    }

    /**
     * Get the user's Google Play Real-Time Developer Notifications.
     */
    public function googlePlayRtdns(): HasMany
    {
        return $this->hasMany(GooglePlayRtdn::class);
    }

    /**
     * Get the user's Google Play purchases.
     */
    public function googlePlayPurchases(): HasMany
    {
        return $this->hasMany(GooglePlayPurchase::class);
    }

    /**
     * Get the user's JVZoo transactions.
     */
    public function jvzooTransactions(): HasMany
    {
        return $this->hasMany(JVZooTransaction::class);
    }
}
