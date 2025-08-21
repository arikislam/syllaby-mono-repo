<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Contracts\Pagination\Paginator;

class RetentionPlanController extends Controller
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
        if (! $plans = $this->fetchPlans()) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithPagination(ProductResource::collection($plans));
    }

    /**
     * Gets all available plans and its prices.
     */
    private function fetchPlans(): Paginator
    {
        $products = config('services.stripe.retention');

        return Plan::with(['prices' => fn ($query) => $query->orderBy('price', 'desc')])
            ->where('type', 'product')
            ->whereIn('plan_id', $products)
            ->paginate(10);
    }
}
