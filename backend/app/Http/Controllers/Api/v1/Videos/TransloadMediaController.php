<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Exception;
use App\Syllaby\Videos\Video;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Assets\TransloadMediaRequest;
use App\Syllaby\Assets\Actions\TransloadMediaAction;

class TransloadMediaController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Uploads the given and asset from a remote url.
     */
    public function store(Video $video, TransloadMediaRequest $request, TransloadMediaAction $upload): JsonResponse
    {
        $this->authorize('update', $video);

        try {
            $media = $upload->handle($video, $request->validated('url'), collection: 'assets');
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(MediaResource::make($media), Response::HTTP_CREATED);
    }
}
