<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use App\Http\Resources\RealCloneResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\RealClones\GenerateRealCloneRequest;
use App\Syllaby\RealClones\Actions\GenerateRealCloneAction;

class GenerateRealCloneController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Triggers the generation process for speech and real clone.
     */
    public function store(RealClone $clone, GenerateRealCloneRequest $request, GenerateRealCloneAction $generator): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $clone = $generator->handle($clone, $this->user());

        return $this->respondWithResource(
            resource: RealCloneResource::make($clone),
            status: Response::HTTP_ACCEPTED,
            message: 'Real Clone queued for generation'
        );
    }
}
