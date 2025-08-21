<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use App\Http\Resources\RealCloneResource;
use App\Http\Requests\Generators\GenerateScriptRequest;
use App\Syllaby\Generators\Actions\GenerateAvatarScriptAction;

class ScriptGeneratorController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Generates a video script for the given real clone.
     */
    public function update(RealClone $clone, GenerateScriptRequest $request, GenerateAvatarScriptAction $generate): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('update', $clone);

        if (! $clone = $generate->handle($clone, $request->validated())) {
            return $this->errorInternalError(__("videos.script_failed"));
        }

        $clone->load('generator');

        return $this->respondWithResource(RealCloneResource::make($clone));
    }
}
