<?php

namespace App\Http\Controllers\Api\v1\Videos;

use App\Syllaby\Videos\Video;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VideoRenderController extends Controller
{
    /**
     * Display the video for the given media uuid.
     */
    public function show(string $uuid): JsonResponse
    {
        if (! $media = $this->fetchMedia($uuid)) {
            return $this->respondWithArray(null);
        }

        $media->load('model');

        $video = $media->model;
        $video->setRelation('media', [$media]);

        return $this->respondWithResource(VideoResource::make($video));
    }

    /**
     * Attempts to fetch the media with the give uuid.
     */
    private function fetchMedia(string $uuid): ?Media
    {
        return Media::where('model_type', (new Video)->getMorphClass())
            ->where('collection_name', 'video')
            ->where('uuid', $uuid)
            ->first();
    }
}
