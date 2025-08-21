<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;

class RealCloneStatusController extends Controller
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
        if (!$clone = RealClone::select(['id', 'user_id', 'status'])->find($id)) {
            return $this->respondWithArray(null);
        }
        $this->authorize('view', $clone);

        return $this->respondWithArray($clone->toArray());
    }
}
