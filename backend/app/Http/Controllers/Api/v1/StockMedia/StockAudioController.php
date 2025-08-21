<?php

namespace App\Http\Controllers\Api\v1\StockMedia;

use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class StockAudioController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Display a list of stock music.
     */
    public function index(Request $request): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $key = 'stock-audio:'.md5($request->fullUrl());
        $ttl = now()->diffInSeconds(now()->addHours(12));

        $musics = Cache::remember($key, $ttl, function () {
            return config('stock-media.audio.samples');
        });

        return $this->respondWithArray($musics);
    }
}
