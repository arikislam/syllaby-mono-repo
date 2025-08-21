<?php

namespace App\Http\Controllers\Api\v1\Clones;

use Illuminate\Http\JsonResponse;
use App\Syllaby\Clonables\Clonable;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\ClonableResource;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Clonables\Actions\DeleteClonableAction;

class CloneController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a list of clone intents.
     */
    public function index(): JsonResponse
    {
        $clones = $this->fetchClone()->latest()->get();

        return $this->respondWithResource(ClonableResource::collection($clones));
    }

    /**
     * Display the clone with the given id.
     */
    public function show(int $id): JsonResponse
    {
        $clone = $this->fetchClone()->where('id', $id)->first();

        $this->authorize('view', $clone);

        return $this->respondWithResource(ClonableResource::make($clone));
    }

    /**
     * Remove a clone from storage.
     */
    public function destroy(Clonable $clonable, DeleteClonableAction $delete): Response|JsonResponse
    {
        $this->authorize('delete', $clonable);

        if (! $delete->handle($clonable)) {
            return $this->errorInternalError();
        }

        return response()->noContent();
    }

    /**
     * Fetch all clone intents for the authenticated user.
     */
    private function fetchClone(): QueryBuilder
    {
        return QueryBuilder::for(Clonable::class)
            ->allowedIncludes(['media', 'model'])
            ->where('user_id', $this->user()->id);
    }
}
