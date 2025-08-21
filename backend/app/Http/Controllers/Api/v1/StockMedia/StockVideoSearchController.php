<?php

namespace App\Http\Controllers\Api\v1\StockMedia;

use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Assets\Contracts\StockVideoContract;

class StockVideoSearchController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected StockVideoContract $videos)
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Display a list of videos matching the search term.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->filled('query')) {
            return $this->errorWrongArgs('Query parameter can not be empty');
        }

        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $key = 'stock-video:'.md5($request->fullUrl());
        $ttl = now()->diffInSeconds(now()->addHours(12));

        $videos = Cache::remember($key, $ttl, function () use ($request) {
            return $this->videos->search($request->query());
        });

        return $this->respondWithArray($videos);
    }
}
