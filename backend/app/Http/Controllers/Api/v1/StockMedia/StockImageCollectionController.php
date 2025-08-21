<?php

namespace App\Http\Controllers\Api\v1\StockMedia;

use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Assets\Contracts\StockImageContract;

class StockImageCollectionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected StockImageContract $images)
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Display a list of collections and featured images.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $key = 'stock-image:'.md5($request->fullUrl());
        $ttl = now()->diffInSeconds(now()->addHours(12));

        $images = Cache::remember($key, $ttl, function () use ($id, $request) {
            return $this->images->collection($id, $request->query());
        });

        return $this->respondWithArray($images);
    }
}
