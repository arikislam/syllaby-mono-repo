<?php

namespace App\Http\Controllers\Api\v1\StockMedia;

use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Assets\Contracts\StockImageContract;

class StockImageSearchController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected StockImageContract $images)
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Display a list of images matching the search term.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->filled('query')) {
            return $this->errorWrongArgs('Query parameter can not be empty');
        }

        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $key = 'stock-image:'.md5($request->fullUrl());
        $ttl = now()->diffInSeconds(now()->addHours(12));

        $images = Cache::remember($key, $ttl, function () use ($request) {
            return $this->images->search($request->query());
        });

        return $this->respondWithArray($images);
    }
}
