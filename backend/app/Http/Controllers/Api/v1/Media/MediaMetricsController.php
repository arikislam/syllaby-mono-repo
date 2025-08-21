<?php

namespace App\Http\Controllers\Api\v1\Media;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Syllaby\Assets\Enums\AssetType;

class MediaMetricsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Get media metrics for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();

        $metrics = DB::selectOne('
            SELECT 
                (SELECT COUNT(*) FROM videos WHERE user_id = ?) as videos_count,
                (SELECT COUNT(*) FROM assets WHERE user_id = ? AND type != ?) as assets_count
        ', [$user->id, $user->id, AssetType::AUDIOS->value]);

        return $this->respondWithArray([
            'videos_count' => $metrics->videos_count,
            'assets_count' => $metrics->assets_count,
        ]);
    }
}
