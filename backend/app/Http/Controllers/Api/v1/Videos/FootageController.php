<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Footage;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\FootageResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Videos\CreateFootageRequest;
use App\Http\Requests\Videos\UpdateFootageRequest;
use App\Syllaby\Videos\Actions\CreateFootageAction;
use App\Syllaby\Videos\Actions\UpdateFootageAction;

class FootageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Creates in storage a video with default footage.
     */
    public function store(CreateFootageRequest $request, CreateFootageAction $create): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $footage = $create->handle($this->user(), $request->validated());

        return $this->respondWithResource(FootageResource::make($footage), Response::HTTP_CREATED);
    }

    /**
     * Updates in storage a given video footage.
     */
    public function update(Footage $footage, UpdateFootageRequest $request, UpdateFootageAction $update): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $footage->loadMissing(['video', 'timeline']);
        $footage = $update->handle($footage, $request->validated());

        return $this->respondWithResource(FootageResource::make($footage));
    }
}
