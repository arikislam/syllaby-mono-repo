<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\RealCloneResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\RealClones\CreateRealCloneRequest;
use App\Http\Requests\RealClones\UpdateRealCloneRequest;
use App\Syllaby\RealClones\Actions\CreateRealCloneAction;
use App\Syllaby\RealClones\Actions\DeleteRealCloneAction;
use App\Syllaby\RealClones\Actions\UpdateRealCloneAction;

class RealCloneController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except(['show', 'destroy']);
    }

    /**
     * Display the real clone with the given id.
     */
    public function show(int $id): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        if (! $clone = $this->fetchRealClone($id)) {
            return $this->respondWithArray(null);
        }

        $this->authorize('view', $clone);

        return $this->respondWithResource(RealCloneResource::make($clone));
    }

    /**
     * Creates in storage a Real Clone record.
     */
    public function store(CreateRealCloneRequest $request, CreateRealCloneAction $create): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $provider = $request->validated('provider');

        if (! $clone = $create->handle($this->user(), $provider, $request->validated())) {
            return $this->errorInternalError('Whoops! It was not possible to generate the real clone.');
        }

        return $this->respondWithResource(RealCloneResource::make($clone), Response::HTTP_CREATED);
    }

    /**
     * Update in storage the given real clone.
     */
    public function update(RealClone $clone, UpdateRealCloneRequest $request, UpdateRealCloneAction $update): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        if (! $clone = $update->handle($clone, $request->validated())) {
            return $this->errorInternalError('Whoops! It was not possible to generate the real clone.');
        }

        return $this->respondWithResource(RealCloneResource::make($clone));
    }

    /**
     * Deletes from storage the given real clone.
     */
    public function destroy(RealClone $clone, DeleteRealCloneAction $delete): Response|JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('delete', $clone);

        if (! $delete->handle($clone)) {
            return $this->errorInternalError('Whoops! We could not delete the real clone.');
        }

        return response()->noContent();
    }

    /**
     * Fetch the real clone with the given id.
     */
    private function fetchRealClone(int $id): ?RealClone
    {
        return QueryBuilder::for(RealClone::class)
            ->allowedIncludes(['media', 'avatar', 'voice', 'video'])
            ->find($id);
    }
}
