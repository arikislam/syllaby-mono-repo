<?php

namespace App\Http\Controllers\Api\v1\Generators;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class GeneratorOptionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display the common options for a generator (style, tone, etc.)
     */
    public function index(): JsonResponse
    {
        return $this->respondWithArray(config('generators.options'));
    }
}
