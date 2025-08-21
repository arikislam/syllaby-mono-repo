<?php

namespace App\Http\Controllers\Api\v1\Metadata;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Publisher\Channels\Services\Youtube\MetadataService;

class YoutubeCategoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function index(MetadataService $metadata): JsonResponse
    {
        return $this->respondWithArray($metadata->categories());
    }
}
