<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\AllowedSort;
use App\Http\Resources\VideoResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Syllaby\Videos\Filters\StatusFilter;
use App\Syllaby\Videos\Filters\PublishedFilter;
use App\Syllaby\Videos\Filters\GenreFilter;
use App\Http\Requests\Videos\UpdateVideoRequest;
use App\Syllaby\Videos\Actions\DeleteVideoAction;
use App\Syllaby\Videos\Actions\UpdateVideoAction;

class VideoController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->only('update');
    }

    /**
     * Display a list of videos.
     */
    public function index(Request $request): JsonResponse
    {
        $includes = array_filter(explode(',', $request->query('include')));

        $query = Video::ownedBy($this->user());
        $videos = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::partial('title'),
                AllowedFilter::custom('status', new StatusFilter),
                AllowedFilter::custom('published', new PublishedFilter),
                AllowedFilter::custom('genre', new GenreFilter),
            ])->allowedSorts([
                AllowedSort::field('title'),
                AllowedSort::field('date', 'updated_at'),
            ])->allowedIncludes($includes);

        if ($request->query('folder')) {
            $videos->join('resources', function ($join) {
                $join->on('videos.id', '=', 'resources.model_id')->where('resources.model_type', 'video');
            })
                ->where('resources.parent_id', $request->query('folder'))
                ->select('videos.*');
        }

        $videos = $videos->paginate($this->take());

        return $this->respondWithPagination(VideoResource::collection($videos));
    }

    /**
     * Fetch a given video by id.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $query = Video::ownedBy($this->user())->where('id', $id);
        $includes = array_filter(explode(',', $request->query('include')));

        if (! $video = QueryBuilder::for($query)->allowedIncludes($includes)->first()) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithResource(VideoResource::make($video));
    }

    /**
     * Update generic video details in storage.
     */
    public function update(Video $video, UpdateVideoRequest $request, UpdateVideoAction $update): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        if (! $video = $update->handle($video, $request->validated())) {
            return $this->errorInternalError('Whoops! We were not able to update the video.');
        }

        return $this->respondWithResource(VideoResource::make($video));
    }

    /**
     * Deletes the given video from storage.
     */
    public function destroy(Request $request, Video $video, DeleteVideoAction $delete): Response|JsonResponse
    {
        $this->authorize('delete', $video);

        $request->validate(['delete_unused_assets' => ['nullable', 'boolean']]);

        $delete->handle($video, $request->delete_unused_assets ?? false);

        return $this->respondWithMessage("Accepted", Response::HTTP_ACCEPTED);
    }
}
