<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Stripe\Subscription;
use Illuminate\Support\Arr;
use App\Syllaby\Analytics\Enum\FeatureFlag;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Analytics\Contracts\AbTester;
use App\Syllaby\Analytics\Enum\AnalyticsType;
use App\Syllaby\Subscriptions\Events\SubscriptionCreated;

class TrackTrialStartedListener implements ShouldQueue
{
    public function __construct(private readonly AbTester $posthog) {}

    public function handle(SubscriptionCreated $event): void
    {
        $user = $event->user;

        $status = Arr::get($event->payload, 'data.object.status');

        if ($status !== Subscription::STATUS_TRIALING) {
            return;
        }

        $variant = $this->posthog->getFeatureFlag(FeatureFlag::TRIAL_CONVERSION->value, $event->user->id) ?? 'control';

        $this->posthog->capture([
            'distinctId' => $user->id,
            'event' => AnalyticsType::TRIAL_STARTED->value,
            'properties' => [
                '$set' => ['name' => $user->name],
                '$feature/feature-flag-key' => $variant,
            ],
        ]);
    }
}
