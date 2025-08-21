<?php

namespace App\Http\Controllers\Api\v1\Presets;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\Syllaby\Presets\FacelessPreset;
use App\Http\Resources\FacelessPresetResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Presets\ManageFacelessPresetRequest;
use App\Syllaby\Presets\Actions\CreateFacelessPresetAction;
use App\Syllaby\Presets\Actions\UpdateFacelessPresetAction;

class FacelessPresetController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except('index');
    }

    /**
     * Display a listing of the faceless presets.
     */
    public function index(Request $request): JsonResponse
    {
        $includes = array_filter(explode(',', $request->query('include')));

        $presets = QueryBuilder::for(FacelessPreset::class)
            ->allowedIncludes($includes)
            ->where('user_id', $this->user()->id)
            ->latest('id')
            ->get();

        return $this->respondWithResource(FacelessPresetResource::collection($presets));
    }

    /**
     * Store a newly created faceless preset in storage.
     */
    public function store(ManageFacelessPresetRequest $request, CreateFacelessPresetAction $action): JsonResponse
    {
        $preset = $action->handle($this->user(), $request->validated());

        return $this->respondWithResource(FacelessPresetResource::make($preset), Response::HTTP_CREATED);
    }

    /**
     * Update the specified faceless preset in storage.
     */
    public function update(ManageFacelessPresetRequest $request, FacelessPreset $preset, UpdateFacelessPresetAction $action): JsonResponse
    {
        $this->authorize('update', $preset);

        $preset = $action->handle($preset, $request->validated());

        return $this->respondWithResource(FacelessPresetResource::make($preset));
    }

    public function destroy(FacelessPreset $preset)
    {
        $this->authorize('delete', $preset);

        $preset->delete();

        return response()->noContent();
    }
}
