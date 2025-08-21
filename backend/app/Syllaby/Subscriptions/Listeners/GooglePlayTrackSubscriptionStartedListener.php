<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Syllaby\Analytics\Enum\FeatureFlag;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Analytics\Contracts\AbTester;
use App\Syllaby\Analytics\Enum\AnalyticsType;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionPurchased;

readonly class GooglePlayTrackSubscriptionStartedListener implements ShouldQueue
{
    public function __construct(private AbTester $posthog) {}

    public function handle(GooglePlaySubscriptionPurchased $event): void
    {
        $user = $event->user;
        $rtdn = $event->rtdn;

        // Get the Google Play subscription if available
        $subscription = $user->googlePlaySubscriptions()
            ->where('purchase_token', $rtdn->purchase_token)
            ->first();

        $variant = $this->posthog->getFeatureFlag(FeatureFlag::TRIAL_CREDITS_EXPERIMENT->value, $user->id) ?? 'control';

        $this->posthog->capture([
            'distinctId' => $user->id,
            'event' => AnalyticsType::SUBSCRIPTION_STARTED->value,
            'properties' => [
                '$set' => ['name' => $user->name],
                '$feature/feature-flag-key' => $variant,
                'subscription_provider' => 'google_play',
                'plan_id' => $rtdn->plan_id,
                'plan_name' => $rtdn->plan?->name,
                'product_id' => $subscription?->product_id,
            ],
        ]);
    }
}
