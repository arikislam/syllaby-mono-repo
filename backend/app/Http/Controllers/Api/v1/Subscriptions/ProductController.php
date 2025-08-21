<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use App\Http\Resources\PriceResource;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get given type of onr time products.
     */
    public function index(string $type): JsonResponse
    {
        $query = Plan::active()->oneTime()->where('meta->type', $type)->with(['googlePlayPlan'])->orderBy('price', 'desc');
        if ($this->user()->usesGooglePlay()) {
            $query->whereHas('googlePlayPlan', function ($query) {
                $query->whereNotNull('metadata');
            });
        }

        $products = $query->get();
        if (! $products) {
            return $this->errorInternalError('Whoops! No products found.');
        }

        return $this->respondWithResource(PriceResource::collection($products));
    }
}
