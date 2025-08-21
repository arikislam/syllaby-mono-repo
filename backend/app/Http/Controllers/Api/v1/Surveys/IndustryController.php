<?php

namespace App\Http\Controllers\Api\v1\Surveys;

use Illuminate\Http\JsonResponse;
use App\Syllaby\Surveys\Industry;
use App\Http\Controllers\Controller;
use App\Http\Resources\IndustryResource;

class IndustryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Display a list of available industries.
     */
    public function index(): JsonResponse
    {
        $resource = IndustryResource::collection(Industry::all());

        if ($industry = $this->user()->industries()->first(['industries.id'])) {
            return $this->respondWithPagination($resource->additional(['user_industry' => $industry->id]));
        }

        return $this->respondWithResource($resource);
    }
}
