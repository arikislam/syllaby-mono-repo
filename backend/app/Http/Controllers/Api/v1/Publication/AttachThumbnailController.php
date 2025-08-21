<?php

namespace App\Http\Controllers\Api\v1\Publication;

use Exception;
use App\Syllaby\Assets\Asset;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Syllaby\Publisher\Publications\Publication;
use App\Http\Requests\Publication\AttachThumbnailRequest;

class AttachThumbnailController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Attach a thumbnail from a given URL to the publication.
     */
    public function update(AttachThumbnailRequest $request, Publication $publication): JsonResponse
    {
        try {
            $asset = Asset::find($request->validated('asset_id'));
            $media = $asset->getMedia()->first();

            $collection = "{$request->validated('provider')}-thumbnail";
            $media = $media->copy($publication, $collection);

            return $this->respondWithResource(MediaResource::make($media));
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }
}
