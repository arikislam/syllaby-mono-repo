<?php

namespace App\Http\Controllers\Api\v1\Publication;

use Exception;
use Throwable;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Syllaby\Assets\Actions\UploadMediaAction;
use App\Syllaby\Publisher\Publications\Publication;
use App\Http\Requests\Publication\CreateThumbnailRequest;
use App\Http\Requests\Publication\DestroyThumbnailRequest;

class PublicationThumbnailController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Upload a new thumbnail for the given publication.
     *
     * @throws Throwable
     */
    public function store(CreateThumbnailRequest $request, Publication $publication, UploadMediaAction $action): JsonResponse
    {
        $collection = "{$request->provider}-thumbnail";

        try {
            $media = $action->handle($publication, $request->file('files'), $collection);

            return $this->respondWithResource(MediaResource::collection($media), Response::HTTP_CREATED);
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }

    /**
     * Remove the thumbnail from the given publication.
     *
     * @throws Throwable
     */
    public function destroy(DestroyThumbnailRequest $request, Publication $publication): Response
    {
        $collection = "{$request->provider}-thumbnail";

        $publication->clearMediaCollection($collection);

        return response()->noContent();
    }
}
