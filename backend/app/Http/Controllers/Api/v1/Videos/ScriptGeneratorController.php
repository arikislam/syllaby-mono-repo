<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Feature;
use Throwable;
use App\Syllaby\Videos\Faceless;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacelessResource;
use App\Http\Requests\Generators\GenerateFacelessScriptRequest;
use App\Syllaby\Generators\Actions\GenerateFacelessScriptAction;

class ScriptGeneratorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function update(Faceless $faceless, GenerateFacelessScriptRequest $request, GenerateFacelessScriptAction $action)
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('update', $faceless->video);

        try {
            $faceless = $action->handle($faceless, $request->validated());
        } catch (Throwable $e) {
            return $this->errorInternalError(__('videos.script_failed'));
        }

        return $this->respondWithResource(FacelessResource::make($faceless->load('generator', 'video')));
    }
}
