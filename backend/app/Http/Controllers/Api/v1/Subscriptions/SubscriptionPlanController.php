<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use App\Syllaby\Users\User;
use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;

class SubscriptionPlanController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all active recurring price plans.
     */
    public function index(): JsonResponse
    {
        $user = $this->user();

        if (! $plans = $this->fetchPlans($user)) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithPagination(
            ProductResource::collection($plans)->additional([
                'current_price' => $this->currentPrice($user),
                'allows_trial' => $this->allowForTrial($user),
            ])
        );
    }

    /**
     * Gets all available plans and its prices.
     */
    private function fetchPlans(User $user): Collection
    {
        return match ($user->subscription_provider) {
            SubscriptionProvider::GOOGLE_PLAY => $this->fetchGooglePlayPlans(),
            SubscriptionProvider::JVZOO => $this->fetchJVZooPlans(),
            default => $this->fetchStripePlans(),
        };
    }

    /**
     * User current subscribed price.
     */
    private function currentPrice(User $user): ?int
    {
        if (! $user->subscribed()) {
            return null;
        }

        return $user->plan_id;
    }

    /**
     * Determines if user is still eligible for trial.
     */
    private function allowForTrial(User $user): bool
    {
        return $user->subscriptions()->where('ends_at', '<', now())->doesntExist();
    }

    /**
     * Fetch Stripe plans.
     */
    private function fetchStripePlans(): Collection
    {
        $products = config('services.stripe.products');

        return Plan::with([
            'prices' => fn ($query) => $query->where('active', true)->orderBy('price', 'asc'),
        ])
            ->whereIn('plan_id', $products)
            ->where('type', 'product')
            ->where('active', true)
            ->get();
    }

    /**
     * Fetch JVZoo plans.
     */
    private function fetchJVZooPlans(): Collection
    {
        $products = config('services.stripe.products');
        $prices = array_values(config('services.jvzoo.plans'));

        return Plan::with(['prices' => function ($query) use ($prices) {
            $query->with('jvzoo')->whereIn('plan_id', $prices)->where('active', true)->orderBy('price', 'asc');
        }])
            ->whereIn('plan_id', $products)
            ->where('type', 'product')
            ->where('active', true)
            ->get();
    }

    /**
     * Fetch Google Play plans.
     */
    private function fetchGooglePlayPlans(): Collection
    {
        $products = config('services.stripe.google_play_products', []);
        $googleActivePlayPlans = config('google-play.active_subscription_plans');

        return Plan::with([
            'prices' => fn ($query) => $query->with('googlePlayPlan')
                ->where('active', true)
                ->whereHas('googlePlayPlan',
                    fn ($q) => $q->whereNotNull('metadata')->whereIn('product_id', $googleActivePlayPlans))
                ->orderBy('price', 'asc'),
            'googlePlayPlan',
        ])
            ->where('type', 'product')
            ->whereIn('plan_id', $products)
            ->get();
    }
}
