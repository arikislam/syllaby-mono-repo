<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Footage;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\FootageResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Videos\RenderFootageRequest;
use App\Syllaby\Videos\Actions\RenderFootageAction;

class RenderFootageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Triggers the rendering process for the video footage.
     */
    public function store(Footage $footage, RenderFootageRequest $request, RenderFootageAction $render): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $footage = tap($render->handle($footage, $this->user(), $request->validated()), function ($footage) {
            $footage->unsetRelation('user');
            $footage->loadMissing('video');
        });

        return $this->respondWithResource(
            resource: FootageResource::make($footage),
            status: Response::HTTP_ACCEPTED,
            message: 'Footage rendering queued'
        );
    }
}
