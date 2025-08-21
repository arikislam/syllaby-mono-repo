<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Syllaby\Videos\Enums\VideoStatus;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Assets\Filters\MediaTypeFilter;
use App\Http\Requests\Assets\UploadMediaRequest;
use App\Syllaby\Videos\Vendors\Renders\Timeline;
use App\Syllaby\Assets\Actions\UploadMediaAction;
use App\Syllaby\Videos\Actions\UpdateTimelineAction;
use App\Syllaby\RealClones\Actions\DeleteRealCloneAction;

class VideoMediaController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except('index');
    }

    /**
     * List and filter assets for the given video.
     */
    public function index(Video $video, Request $request): JsonResponse
    {
        $this->authorize('view', $video);

        $perPage = min($request->query('per_page', 10), 20);

        $assets = QueryBuilder::for(Media::class)
            ->allowedFilters([
                AllowedFilter::custom('type', new MediaTypeFilter)->default('image'),
            ])
            ->where('collection_name', 'assets')
            ->where('model_type', $video->getMorphClass())
            ->where('model_id', $video->id)
            ->latest('updated_at')
            ->paginate($perPage);

        return $this->respondWithPagination(MediaResource::collection($assets));
    }

    /**
     * Uploads the given asset to a video.
     */
    public function store(Video $video, UploadMediaRequest $request, UploadMediaAction $upload): JsonResponse
    {
        $this->authorize('update', $video);

        try {
            $media = $upload->handle($video, $request->file('files'), collection: 'assets');
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(MediaResource::collection($media), Response::HTTP_CREATED);
    }

    /**
     * Removes the given asset from storage and every reference
     * of it from the video timeline.
     */
    public function destroy(Video $video, Media $asset): Response
    {
        $this->authorize('update', $video);

        $video->load('footage.timeline');
        $timeline = $video->footage->timeline;

        $elements = Timeline::from(Arr::get($timeline->content, 'elements', []))->detach($asset);
        $source = array_merge($timeline->content, ['elements' => $elements]);

        $deleted = DB::transaction(function () use ($video, $asset, $timeline, $source) {
            app(UpdateTimelineAction::class)->handle($timeline, $source);

            $video->update([
                'synced_at' => null,
                'updated_at' => now(),
                'status' => VideoStatus::DRAFT,
            ]);

            return match ($asset->model_type) {
                $video->getMorphClass() => $asset->delete(),
                default => app(DeleteRealCloneAction::class)->handle($asset->model)
            };
        });

        return match (true) {
            $deleted => response()->noContent(),
            default => $this->errorInternalError()
        };
    }
}
