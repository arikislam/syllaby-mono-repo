<?php

namespace App\Syllaby\Subscriptions\Commands;

use Closure;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Credits\CreditEvent;
use Illuminate\Support\LazyCollection;
use App\Syllaby\Subscriptions\JVZooPlan;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\JVZooSubscription;

class ReleaseMonthlyJVZooCreditsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'syllaby:release-jvzoo-credits';

    private Collection $events;

    /**
     * The console command description.
     */
    protected $description = 'Release monthly credits to users with yearly JVZoo subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = now();
        $this->events = $this->fetchCreditEvents();

        if ($this->events->isEmpty()) {
            return;
        }

        $prices = $this->fetchYearlyPrices();

        $this->fetchSubscriptions($prices)->each(function ($subscription) use ($now) {
            $user = $subscription->user;
            $event = CreditEventEnum::MONTHLY_CREDITS_ADDED;

            if (! $this->shouldReceiveCredits($subscription, $now)) {
                return;
            }

            $amount = max(0, $user->monthly_credit_amount);
            (new CreditService($user))->increment($event, null, $amount);
        });
    }

    /**
     * Get plans.
     */
    private function fetchYearlyPrices(): Collection
    {
        $products = config('services.jvzoo.plans', []);

        return JVZooPlan::query()->where('interval', 'year')
            ->whereIn('plan_id', $products)
            ->where('is_active', true)
            ->get();

    }

    /**
     * Fetch subscriptions created on current day.
     */
    private function fetchSubscriptions(Collection $prices): LazyCollection
    {
        return JVZooSubscription::query()->with('user')
            ->whereIn('jvzoo_plan_id', $prices->pluck('id')->toArray())
            ->where('status', JVZooSubscription::STATUS_ACTIVE)
            ->whereNotExists($this->hasMonthlyCredits())
            ->whereExists($this->hasInitialCredits())
            ->whereDate('trial_ends_at', '<', today())
            ->where($this->anniversary())
            ->lazyById(200, 'id');
    }

    /**
     * Determine if user should receive credits.
     */
    private function shouldReceiveCredits(JVZooSubscription $subscription, Carbon $now): bool
    {
        $user = $subscription->user;

        return match (true) {
            blank($user) => false,
            ! $this->isReleaseDay($subscription, $now) => false,
            blank($user->monthly_credit_amount) => false,
            default => true,
        };
    }

    /**
     * Determine if today is the monthly anniversary for a subscription.
     */
    private function isReleaseDay(JVZooSubscription $subscription, Carbon $now): bool
    {
        $anchor = $subscription->trial_ends_at;

        if (blank($anchor)) {
            return false;
        }

        if (! $anchor instanceof Carbon) {
            $anchor = Carbon::parse($anchor);
        }

        if ($anchor->day > $now->daysInMonth) {
            return $now->isLastOfMonth();
        }

        return $anchor->day === $now->day;
    }

    /**
     * Check if user has received initial credits.
     */
    private function hasInitialCredits(): Closure
    {
        $event = $this->events->firstWhere('name', CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value);

        return fn ($query) => $query->select(DB::raw(1))->from('credit_histories')
            ->whereColumn('credit_histories.user_id', 'jvzoo_subscriptions.user_id')
            ->where('credit_events_id', $event->id);
    }

    /**
     * Check if today is the monthly anniversary for a subscription.
     */
    private function anniversary(): Closure
    {
        $today = today();
        $isLastDay = $today->isLastOfMonth();
        $day = $today->day;
        $daysInMonth = $today->daysInMonth;

        return fn ($query) => $query->whereDay('trial_ends_at', $day)->when(
            $isLastDay, fn ($q) => $q->orWhereDay('trial_ends_at', '>', $daysInMonth)
        );
    }

    /**
     * Check if user has received monthly credits today.
     */
    private function hasMonthlyCredits(): Closure
    {
        $start = today()->startOfMonth();
        $end = today()->endOfMonth();
        $event = $this->events->firstWhere('name', CreditEventEnum::MONTHLY_CREDITS_ADDED->value);

        return fn ($query) => $query->select(DB::raw(1))->from('credit_histories')
            ->whereColumn('credit_histories.user_id', 'jvzoo_subscriptions.user_id')
            ->where('credit_events_id', $event->id)
            ->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Get credit events.
     */
    private function fetchCreditEvents(): Collection
    {
        return CreditEvent::query()->whereIn('name', [
            CreditEventEnum::MONTHLY_CREDITS_ADDED->value,
            CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value,
        ])->get();
    }
}
