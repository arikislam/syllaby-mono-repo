<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Carbon\Carbon;
use Stripe\Subscription;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Shared\TikTok\TikTok;
use App\Shared\Facebook\Pixel;
use App\Syllaby\Subscriptions\Plan;
use App\System\Traits\HandlesFeatures;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\Events\SubscriptionUpdated;
use App\Syllaby\Subscriptions\Actions\ReleaseSchedulerAction;
use App\Syllaby\Subscriptions\Notifications\UserSwappedPlans;

class HandlePlanFeaturesListener
{
    use HandlesFeatures;

    /**
     * Handle the event.
     */
    public function handle(SubscriptionUpdated $event): void
    {
        $user = $event->user;
        $payload = $event->payload;

        if ($this->trialHasEnded($payload)) {
            $this->trackPaidConversion($user, $payload);
            $this->setPlanFullCredits($user, $payload);
        }

        if ($this->planWasUpgraded($payload)) {
            $this->notifyUpgrade($user, $payload);
        }

        if ($this->planWasDowngraded($payload)) {
            $this->notifyDowngrade($user, $payload);
        }

        $this->refreshFeaturesFor($user);
    }

    /**
     * Set maximum credits allowed for the user relative to the current plan.
     */
    private function setPlanFullCredits(User $user, array $payload): void
    {
        $credits = $this->credits($payload);
        $type = CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID;

        $anchor = Arr::get($payload, 'data.object.billing_cycle_anchor');
        $user->subscription()->update(['cycle_anchor_at' => Carbon::parse($anchor)]);

        (new CreditService($user))->set($credits, $type, $credits, 'New Plan Started - Trial Ended');
    }

    /**
     * Purge old plan features and notify the user.
     */
    private function notifyUpgrade(User $user, array $payload): void
    {
        $data = $this->previousPlan($payload);
        $plan = Plan::where('plan_id', Arr::get($data, 'id'))->first();

        $this->updateUserPlan($user, $payload);

        $total = $this->credits($payload);
        $type = CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID;

        (new CreditService($user))->set($total, $type, $total, 'New Plan Started - Upgraded');

        // TODO: Have separate notification for upgrades.
        $user->notify(new UserSwappedPlans($plan));
    }

    /**
     * Purge old plan features and notify the user.
     */
    private function notifyDowngrade(User $user, array $payload): void
    {
        $data = $this->previousPlan($payload);
        $plan = Plan::where('plan_id', Arr::get($data, 'id'))->first();

        $this->updateUserPlan($user, $payload);

        $total = $this->credits($payload);
        $type = CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID;

        (new CreditService($user))->set($total, $type, $total, 'New Plan Started - Downgrade');

        app(ReleaseSchedulerAction::class)->handle($user->subscription());

        // TODO: Have separate notification for downgrades.
        $user->notify(new UserSwappedPlans($plan));
    }

    /**
     * Update the user plan.
     */
    private function updateUserPlan(User $user, array $payload): void
    {
        $data = $this->currentPlan($payload);
        $current = Plan::where('plan_id', Arr::get($data, 'id'))->first();
        $anchor = Arr::get($payload, 'data.object.billing_cycle_anchor');

        $user->update(['plan_id' => $current->id]);
        $user->subscription()->update(['cycle_anchor_at' => Carbon::parse($anchor)]);
    }

    /**
     * Checks if the user upgraded plans.
     */
    private function planWasUpgraded(array $payload): bool
    {
        $current = $this->currentPlan($payload);
        $previous = $this->previousPlan($payload);

        if (blank($current) || blank($previous)) {
            return false;
        }

        return Arr::get($current, 'unit_amount') > Arr::get($previous, 'unit_amount');
    }

    /**
     * Checks if the user downgraded plans.
     */
    private function planWasDowngraded(array $payload): bool
    {
        $current = $this->currentPlan($payload);
        $previous = $this->previousPlan($payload);

        if (blank($current) || blank($previous)) {
            return false;
        }

        return Arr::get($current, 'unit_amount') < Arr::get($previous, 'unit_amount');
    }

    /**
     * Check whether the user trial has ended
     */
    private function trialHasEnded(array $payload): bool
    {
        $status = Arr::get($payload, 'data.previous_attributes.status');

        return filled($status) && $status === Subscription::STATUS_TRIALING;
    }

    /**
     * Get current subscribed price details.
     */
    private function currentPlan(array $payload): ?array
    {
        return Arr::get($payload, 'data.object.items.data.0.price');
    }

    /**
     * In case of plan swap gets previously subscribed price details.
     */
    private function previousPlan(array $payload): ?array
    {
        return Arr::get($payload, 'data.previous_attributes.items.data.0.price');
    }

    /**
     * Get the credits for the plan.
     */
    private function credits(array $payload): int
    {
        $plan = $this->currentPlan($payload);

        $interval = Arr::get($plan, 'recurring.interval', 'month');
        $credits = (int) Arr::get($plan, 'metadata.full_credits', 0);

        return $interval === 'month' ? $credits : $credits / 12;
    }

    /**
     * Track paid conversion.
     */
    private function trackPaidConversion(User $user, array $payload): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $id = Str::uuid();
        $price = $this->currentPlan($payload);
        $plan = Plan::where('plan_id', Arr::get($price, 'id'))->first();

        $payload = [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
            'url' => config('app.frontend_url'),
            'cookies' => [
                'tiktok' => Arr::get($user->ad_tracking, 'tiktok', []),
                'facebook' => Arr::get($user->ad_tracking, 'facebook', []),
            ],
            'custom' => [
                'quantity' => 1,
                'currency' => 'USD',
                'product_id' => $plan->id,
                'amount' => round(Arr::get($price, 'unit_amount', 0) / 100, 2),
            ],
        ];

        TikTok::track($id, 'Subscribe', array_merge($payload, [
            'source' => 'offline',
        ]));

        Pixel::track($id, 'Subscribe', array_merge($payload, [
            'source' => 'system_generated',
        ]));
    }
}
