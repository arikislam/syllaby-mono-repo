<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\Collection;

class StoragePlanController extends Controller
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
        if (! $plans = $this->fetchPlan()) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithResource(ProductResource::collection($plans));
    }

    /**œ
     * Gets all available plans and its prices.
     */
    private function fetchPlan(): Collection
    {
        $recurrence = $this->user()->plan->type;

        return Plan::with([
            'prices' => fn ($query) => $query->where('type', $recurrence)->with('googlePlayPlan')->orderBy('price', 'asc'),
            'googlePlayPlan',
        ])
            ->where('plan_id', config('services.stripe.add_ons.storage.product'))
            ->where('type', 'product')
            ->get();
    }
}
