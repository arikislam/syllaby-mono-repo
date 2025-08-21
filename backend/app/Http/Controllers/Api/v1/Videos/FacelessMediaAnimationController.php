<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Exception;
use Illuminate\Http\Request;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Videos\Events\VideoModified;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Animation\CreateAnimationRequest;
use App\Http\Requests\Animation\BulkAnimationStatusRequest;
use App\Syllaby\Animation\Actions\BulkCreateAnimationsAction;

class FacelessMediaAnimationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function show(Request $request, Faceless $faceless)
    {
        $id = $request->query('id');

        if ($id === null) {
            throw ValidationException::withMessages(['id' => 'The id field is required in query params']);
        }

        $asset = Asset::where('user_id', $faceless->user_id)->where('id', $id)->first();

        if ($asset === null) {
            throw ValidationException::withMessages(['id' => 'The asset with the given id was not found']);
        }

        $asset = $faceless->assets()
            ->where('video_assets.asset_id', $asset->id)
            ->with('media')
            ->first();

        return $this->respondWithResource(AssetResource::make($asset));
    }

    public function store(CreateAnimationRequest $request, Faceless $faceless, BulkCreateAnimationsAction $action)
    {
        try {
            $assets = $action->handle($faceless, $request->validated('animations'));

            event(new VideoModified($faceless->video));

            $assets = $faceless->assets()
                ->whereIn('video_assets.asset_id', $assets->pluck('id'))
                ->with('media')
                ->get();

            return AssetResource::collection($assets);
        } catch (Exception $exception) {
            return $this->respondWithMessage($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function bulkStatus(BulkAnimationStatusRequest $request, Faceless $faceless)
    {
        $assets = $request->validated('assets');

        $animations = $faceless->assets()
            ->where('type', AssetType::AI_VIDEO)
            ->whereIn('video_assets.asset_id', $assets)
            ->whereNotNull('assets.parent_id') // Ensure they are animations
            ->with('media')
            ->get();

        return AssetResource::collection($animations)->additional([
            'requested' => count($assets),
            'returned' => $animations->count(),
        ]);
    }
}
