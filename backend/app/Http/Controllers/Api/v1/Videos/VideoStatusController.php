<?php

namespace App\Http\Controllers\Api\v1\Videos;

use App\Syllaby\Videos\Video;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class VideoStatusController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display the real clone status the given id.
     */
    public function show(int $id): JsonResponse
    {
        if (!$video = Video::select(['id', 'user_id', 'status'])->find($id)) {
            return $this->respondWithArray(null);
        }

        $this->authorize('view', $video);

        return $this->respondWithArray($video->toArray());
    }
}
