<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Syllaby\Videos\Events\VideoModified;
use App\Syllaby\Assets\DTOs\AssetCreationData;
use App\Syllaby\Assets\Actions\CreateFacelessAssetAction;
use App\Syllaby\Assets\Actions\UploadMediaAction;
use App\Http\Requests\Videos\GenerateImageRequest;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Videos\Actions\RegenerateImageAction;
use App\Http\Requests\Videos\UploadFacelessImageRequest;
use App\Http\Requests\Videos\TransloadFacelessMediaRequest;

class FacelessMediaManagerController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Uploads a temporary replacement image for the given faceless slider position.
     */
    public function store(UploadFacelessImageRequest $request, Faceless $faceless, CreateFacelessAssetAction $action, UploadMediaAction $upload): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('update', $faceless);

        try {
            $data = AssetCreationData::forCustomMedia($faceless, $request->file('file'), $request->integer('index'));

            $asset = $action->handle($faceless, $data);

            $upload->handle($asset, Arr::wrap($request->file('file')));

            event(new VideoModified($faceless->video));

            $asset = $faceless->assets()
                ->where('video_assets.asset_id', $asset->id)
                ->where('video_assets.order', $request->integer('index'))
                ->with('media')
                ->first();

            return $this->respondWithResource(AssetResource::make($asset));
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }

    /**
     * Generates a temporary replacement image with AI for the given faceless slider position.
     */
    public function update(GenerateImageRequest $request, Faceless $faceless, RegenerateImageAction $regenerate): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $user = $this->user();
        $tracker = $request->tracker;

        try {
            $asset = $regenerate->handle($faceless, $user, $request->validated(), $tracker);

            event(new VideoModified($faceless->video));

            $asset = $faceless->assets()->with('media')
                ->where('assets.id', $asset->id)
                ->first();

            return $this->respondWithResource(AssetResource::make($asset)->additional([
                'image_generation' => [
                    'count' => $tracker->count,
                    'limit' => $tracker->limit,
                ],
            ]));
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }

    public function transload(TransloadFacelessMediaRequest $request, Faceless $faceless, CreateFacelessAssetAction $action, TransloadMediaAction $upload)
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('update', $faceless);

        try {
            $data = AssetCreationData::forStockMedia($faceless, Arr::get($request->details, 'mime-type'), $request->integer('index'));

            $asset = $action->handle($faceless, $data);

            $upload->handle($asset, $request->validated('url'));

            event(new VideoModified($faceless->video));

            $asset = $faceless->assets()
                ->where('video_assets.asset_id', $asset->id)
                ->where('video_assets.order', $request->integer('index'))
                ->with('media')
                ->first();

            return $this->respondWithResource(AssetResource::make($asset));
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }
}
