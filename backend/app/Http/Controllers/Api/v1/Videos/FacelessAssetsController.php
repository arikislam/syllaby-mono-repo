<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Illuminate\Http\Request;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Syllaby\Videos\Events\VideoModified;
use App\Http\Requests\Videos\UpdateFacelessAssetRequest;

class FacelessAssetsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function index(Request $request, Faceless $faceless)
    {
        $this->authorize('view', $faceless);

        $index = $request->query('index');

        $assets = $faceless->assets()->with('media')->when(
            is_numeric($index),
            fn ($query) => $query->where('video_assets.order', $index),
            fn ($query) => $query->where('video_assets.active', true)->oldest('video_assets.order')
        )->get();

        return $this->respondWithResource(AssetResource::collection($assets));
    }

    public function show(Faceless $faceless, Asset $asset)
    {
        // Assets endpoint should be independent of the faceless video
        // Wich us good Luck trying to find time to refactor this
        $this->authorize('view', $faceless);
        $this->authorize('view', $asset);

        $asset->loadMissing('media');

        return $this->respondWithResource(new AssetResource($asset));
    }

    public function update(UpdateFacelessAssetRequest $request, Faceless $faceless)
    {
        DB::transaction(function () use ($request, $faceless) {
            $faceless->assets()->where('video_assets.order', $request->validated('index'))->update(['active' => false]);
            $faceless->assets()->sync([$request->validated('id') => ['order' => $request->validated('index'), 'active' => true]], detaching: false);
        });

        event(new VideoModified($faceless->video));

        return $this->respondWithResource(AssetResource::make(
            $faceless->assets()->with('media')->find($request->validated('id'))
        ));
    }
}
