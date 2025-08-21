<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\FacelessResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Videos\CreateFacelessRequest;
use App\Http\Requests\Videos\UpdateFacelessRequest;
use App\Syllaby\Videos\Actions\CreateFacelessAction;
use App\Syllaby\Videos\Actions\UpdateFacelessAction;

class FacelessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except('show');
    }

    /**
     * Display the given faceless video details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        /** @var Faceless $faceless */
        $query = Faceless::query()->where('id', $id);

        $includes = array_filter(explode(',', $request->query('include')));

        if (! $faceless = QueryBuilder::for($query)->allowedIncludes($includes)->first()) {
            return $this->respondWithArray(null);
        }

        $this->authorize('view', $faceless);

        return $this->respondWithResource(FacelessResource::make($faceless));
    }

    public function store(CreateFacelessRequest $request, CreateFacelessAction $action): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $faceless = $action->handle($this->user(), $request->validated());

        return $this->respondWithResource(FacelessResource::make($faceless->loadMissing('video')), Response::HTTP_CREATED);
    }

    public function update(Faceless $faceless, UpdateFacelessRequest $request, UpdateFacelessAction $action): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $faceless = $action->handle($faceless, $request->validated());

        return $this->respondWithResource(FacelessResource::make($faceless));
    }
}
