<?php

namespace App\Http\Controllers\Api\v1\Assets;

use Illuminate\Support\Arr;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\DTOs\AssetCreationData;
use App\Http\Requests\Assets\UploadAssetRequest;
use App\Syllaby\Assets\Actions\UploadMediaAction;
use App\Syllaby\Assets\Actions\CreateStandaloneAssetAction;

class UploadAssetController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Upload a new standalone asset.
     */
    public function store(UploadAssetRequest $request, CreateStandaloneAssetAction $create, UploadMediaAction $media): JsonResponse
    {
        $file = $request->file('file');

        $genre = $request->has('genre_id') ? Genre::find($request->genre_id) : null;
        
        $type = AssetType::fromMime($file->getMimeType(), 'custom');
        
        $name = $request->get('name') ?? $file->getClientOriginalName();

        $data = AssetCreationData::forStandaloneUpload($request->user(), $type, $name, $genre);

        $asset = $create->handle($data);

        $media->handle($asset, Arr::wrap($file));

        return $this->respondWithResource(AssetResource::make($asset->load('media')));
    }
}
