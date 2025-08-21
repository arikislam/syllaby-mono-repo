<?php

namespace App\Http\Controllers\Api\v1\Assets;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\SortAssetsRequest;
use App\Syllaby\Assets\Actions\SortAssetsAction;

class SortAssetController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Re-order assets based on drag and drop position.
     */
    public function update(SortAssetsRequest $request, SortAssetsAction $sort): JsonResponse
    {
        if (! $sort->handle($request->asset, $request->reference)) {
            return $this->internalError('Failed to sort assets');
        }

        return $this->respondWithMessage('Assets sorted successfully');
    }
}
