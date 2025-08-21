<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Log;
use App\System\Traits\HandlesFeatures;
use App\Syllaby\Analytics\Enum\FeatureFlag;
use App\Syllaby\Analytics\Contracts\AbTester;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Subscriptions\CardFingerprint;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\Events\SubscriptionCreated;
use App\Syllaby\Subscriptions\Notifications\CardAbuseDetected;

class SetSubscriptionCreditsListener
{
    use HandlesFeatures;

    public function __construct(private readonly AbTester $posthog) {}

    /**
     * Set the user initial credits upon subscription.
     */
    public function handle(SubscriptionCreated $event): void
    {
        $user = $event->user;
        $payload = $event->payload;

        [$price, $amount, $type] = $this->getSubscriptionCredits($user, $payload);
        $anchor = Arr::get($payload, 'data.object.billing_cycle_anchor');
        $plan = Plan::where('plan_id', $price)->first();

        DB::transaction(function () use ($user, $amount, $type, $plan, $anchor) {
            $user->update(['plan_id' => $plan->id, 'promo_code' => null]);
            $user->subscription()->update(['cycle_anchor_at' => Carbon::parse($anchor)]);

            (new CreditService($user))->set($amount, $type);
        });

        $this->refreshFeaturesFor($user);

        if (app()->environment('production')) {
            $this->scanForTrialAbuse($user);
        }
    }

    /**
     * Resolves the type of credit to apply given the subscription status.
     */
    private function getSubscriptionCredits(User $user, array $payload): array
    {
        $metadata = 'data.object.items.data.0.price.metadata';
        $plan = Arr::get($payload, 'data.object.items.data.0.price.id');
        $variant = $this->posthog->getFeatureFlag(FeatureFlag::TRIAL_CREDITS_EXPERIMENT->value, $user->id);

        if ($user->onTrial()) {
            return [$plan, Arr::get($payload, "{$metadata}.trial_credits"), CreditEventEnum::SUBSCRIBE_TO_TRIAL];
        }

        $credits = (int) Arr::get($payload, "{$metadata}.full_credits");
        $credits = $this->interval('month', $payload) ? $credits : $credits / 12;

        return [$plan, $credits, CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID];
    }

    /**
     * Check the billing cycle interval.
     */
    private function interval(string $interval, array $payload): bool
    {
        return $interval === Arr::get($payload, 'data.object.items.data.0.price.recurring.interval');
    }

    /**
     * Scan for trial abuse and cancel subscription if detected.
     */
    private function scanForTrialAbuse(User $user): void
    {
        if (! $card = CardFingerprint::where('user_id', $user->id)->latest('updated_at')->first()) {
            return;
        }

        $usage = CardFingerprint::where('fingerprint', $card->fingerprint)
            ->where('updated_at', '>=', now()->subWeek())
            ->count();

        if ($usage < 4) {
            return;
        }

        $user->subscription()->loadMissing('owner');
        $user->subscription()->cancelNow();
        $user->notify(new CardAbuseDetected);

        Log::warning('Trial abuse detected', [
            'id' => $user->id,
            'weekly_usage' => $usage,
            'fingerprint' => $card->fingerprint,
        ]);
    }
}
