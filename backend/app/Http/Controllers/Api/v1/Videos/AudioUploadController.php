<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Http\Requests\Videos\UploadAudioRequest;
use App\Syllaby\Assets\Actions\UploadMediaAction;

class AudioUploadController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Store the audio file.
     */
    public function store(UploadAudioRequest $request, Faceless $faceless, UploadMediaAction $upload): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('update', $faceless);

        $faceless->clearMediaCollection('script');
        $media = $upload->handle($faceless, $request->file('files'), 'script');

        return $this->respondWithResource(MediaResource::make($media[0]));
    }
}
