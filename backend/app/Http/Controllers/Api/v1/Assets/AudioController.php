<?php

namespace App\Http\Controllers\Api\v1\Assets;

use Exception;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Filters\MediaTagFilter;
use App\Http\Requests\Assets\UploadAudioRequest;
use App\Syllaby\Assets\Actions\UploadUserAudioAction;

class AudioController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Display a paginated list of both user and stock audio
     */
    public function index(): JsonResponse
    {
        $user = $this->user();

        $assets = Asset::where('type', AssetType::AUDIOS->value)->ownedBy($user)->pluck('id');

        $musics = QueryBuilder::for(Media::class)->allowedFilters([
            AllowedFilter::custom('tag', new MediaTagFilter),
        ])
            ->where('collection_name', AssetType::AUDIOS->value)
            ->whereIn('model_id', $assets->toArray())
            ->where('model_type', 'asset')
            ->latest('id')
            ->paginate($this->take());

        if ($musics->isEmpty()) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithPagination(MediaResource::collection($musics));
    }

    /**
     * Uploads audio files as part of the user global available assets.
     */
    public function store(UploadAudioRequest $request, UploadUserAudioAction $upload): JsonResponse
    {
        try {
            $user = $this->user();

            $media = $upload->handle($request->file('files'), $user);
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(MediaResource::collection($media));
    }
}
