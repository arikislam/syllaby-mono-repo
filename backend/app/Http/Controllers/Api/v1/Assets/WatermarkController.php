<?php

namespace App\Http\Controllers\Api\v1\Assets;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Http\Requests\Assets\UploadWatermarkRequest;
use App\Syllaby\Assets\Actions\UploadWatermarkAction;

class WatermarkController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(UploadWatermarkRequest $request, UploadWatermarkAction $action)
    {
        $asset = $action->handle($this->user(), $request->validated());

        $asset->loadMissing('media');

        return $this->respondWithResource(AssetResource::make($asset));
    }
}
