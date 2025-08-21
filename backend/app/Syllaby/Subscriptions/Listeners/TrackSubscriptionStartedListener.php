<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Stripe\Subscription;
use Illuminate\Support\Arr;
use App\Syllaby\Analytics\Enum\FeatureFlag;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Analytics\Contracts\AbTester;
use App\Syllaby\Analytics\Enum\AnalyticsType;
use App\Syllaby\Subscriptions\Events\SubscriptionUpdated;

class TrackSubscriptionStartedListener implements ShouldQueue
{
    public function __construct(private readonly AbTester $posthog) {}

    public function handle(SubscriptionUpdated $event): void
    {
        if (! $this->isTrialConverted($event->payload)) {
            return;
        }

        $variant = $this->posthog->getFeatureFlag(FeatureFlag::TRIAL_CREDITS_EXPERIMENT->value, $event->user->id) ?? 'control';

        $this->posthog->capture([
            'distinctId' => $event->user->id,
            'event' => AnalyticsType::SUBSCRIPTION_STARTED->value,
            'properties' => [
                '$set' => ['name' => $event->user->name],
                '$feature/feature-flag-key' => $variant,
            ],
        ]);
    }

    private function isTrialConverted(array $payload): bool
    {
        $status = Arr::get($payload, 'data.previous_attributes.status');

        return filled($status) && $status === Subscription::STATUS_TRIALING;
    }
}
