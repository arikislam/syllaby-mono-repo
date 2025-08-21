<?php

namespace App\Http\Controllers\Api\v1\Publication;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\PublicationResource;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Publisher\Publications\Publication;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValue;
use App\Http\Requests\Publication\CreatePublicationRequest;
use App\Http\Requests\Publication\UpdatePublicationRequest;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use App\Syllaby\Publisher\Publications\Actions\IndexPublicationAction;
use App\Syllaby\Publisher\Publications\Actions\CreatePublicationAction;
use App\Syllaby\Publisher\Publications\Actions\DeletePublicationAction;
use App\Syllaby\Publisher\Publications\Actions\UpdatePublicationAction;

class PublicationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except(['index', 'show']);
    }

    public function index(IndexPublicationAction $action)
    {
        try {
            return $this->respondWithPagination(PublicationResource::collection($action->handle($this->user())));
        } catch (InvalidFilterValue $e) {
            return $this->errorWrongArgs($e->getMessage());
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $includes = array_filter(explode(',', $request->query('include')));
        $publication = QueryBuilder::for(Publication::class)
            ->allowedIncludes($includes)
            ->where('id', $id)
            ->first();

        if (blank($publication)) {
            return $this->errorNotFound('Publication not found.');
        }

        $this->authorize('view', $publication);

        return $this->respondWithResource(PublicationResource::make($publication));
    }

    public function store(CreatePublicationRequest $request, CreatePublicationAction $action): JsonResponse
    {
        try {
            $publication = $action->handle($request->validated(), $this->user());

            return $this->respondWithResource(PublicationResource::make($publication->load(['media', 'event', 'video'])), Response::HTTP_CREATED);
        } catch (FileIsTooBig) {
            return $this->errorWrongArgs('File is Too Big', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        } catch (FileDoesNotExist) {
            return $this->errorNotFound('File Does Not Exists at the given path');
        }
    }

    public function update(UpdatePublicationRequest $request, Publication $publication, UpdatePublicationAction $action): JsonResponse
    {
        try {
            $publication = $action->handle($request->validated(), $publication);

            return $this->respondWithResource(PublicationResource::make($publication->load(['media', 'channels', 'event'])));
        } catch (FileIsTooBig) {
            return $this->errorWrongArgs('File is Too Big', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        } catch (FileDoesNotExist) {
            return $this->errorNotFound('File Does Not Exists at the given path');
        }
    }

    public function destroy(Publication $publication, DeletePublicationAction $delete): Response|JsonResponse
    {
        $this->authorize('delete', $publication);

        if (! $delete->handle($publication)) {
            return $this->errorInternalError('Whoops! We were not able to delete the publication.');
        }

        return response()->noContent();
    }
}
