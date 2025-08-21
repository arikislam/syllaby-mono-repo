<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionCanceled;

class GooglePlaySubscriptionCanceledListener
{
    /**
     * Handle the event.
     */
    public function handle(GooglePlaySubscriptionCanceled $event): void
    {
        $rtdn = $event->rtdn;
        $user = $event->user;

        // Log the cancellation
        Log::info('Google Play subscription canceled', [
            'user_id' => $user->id,
            'rtdn_id' => $rtdn->id,
            'plan_id' => $rtdn->plan_id,
            'purchase_token' => $rtdn->purchase_token,
        ]);

        // TODO: Implement your business logic here
        // Examples:
        // - Update user's subscription status
        // - Cancel access to premium features
        // - Send cancellation email
        // - Update analytics

        // For now, just mark the RTDN as processed if not already
        if ($rtdn->isPending()) {
            $rtdn->markAsProcessed();
        }
    }
}
