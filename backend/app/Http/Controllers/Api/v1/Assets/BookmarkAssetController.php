<?php

namespace App\Http\Controllers\Api\v1\Assets;

use Throwable;
use App\Syllaby\Assets\Asset;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Syllaby\Bookmarks\Actions\ToggleBookmarkAction;

class BookmarkAssetController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Toggle a bookmark on an asset.
     */
    public function update(Asset $asset, ToggleBookmarkAction $action): JsonResponse
    {
        try {
            $model = $action->handle($this->user(), $asset);
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(AssetResource::make($model));
    }
}
