<?php

namespace App\Http\Controllers\Api\v1\Previews;

use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Filters\MediaTagFilter;

class AudioController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Display a paginated list of stock audio
     */
    public function index(): JsonResponse
    {
        $assets = Asset::where('type', AssetType::AUDIOS->value)
            ->whereNull('user_id')
            ->pluck('id');

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
}
