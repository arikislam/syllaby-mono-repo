<?php

namespace App\Http\Controllers\Api\v1\Metadata;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class SocialProviderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        $providers = collect(config('social-account'))->map(fn ($provider) => [
            'title' => $provider['title'],
            'type' => $provider['type'],
            'icon' => asset($provider['icon']),
        ])->values();

        return $this->respondWithArray($providers->all());
    }
}
