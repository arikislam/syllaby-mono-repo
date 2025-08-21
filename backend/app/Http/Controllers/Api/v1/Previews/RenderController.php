<?php

namespace App\Http\Controllers\Api\v1\Previews;

use App\Syllaby\Videos\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Videos\Actions\DeleteVideoAction;
use App\Http\Requests\Previews\RenderPreviewRequest;
use App\Syllaby\Previews\Actions\CreatePreviewAction;
use App\Syllaby\Videos\Jobs\Faceless\TriggerMediaGeneration;
use App\Syllaby\Videos\Jobs\Faceless\GenerateFacelessVoiceOver;

class RenderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Retrieves a video record.
     */
    public function show(Video $video): JsonResponse
    {
        if (! $this->user()->owns($video)) {
            return $this->errorForbidden('Unauthorized');
        }

        $video->load(['media', 'faceless.voice', 'faceless.music']);

        return $this->respondWithResource(VideoResource::make($video));
    }

    /**
     * Creates a new video record and renders it.
     */
    public function store(RenderPreviewRequest $request, CreatePreviewAction $create): JsonResponse
    {
        $video = $create->handle($this->user(), $request->validated());

        Bus::chain([
            new GenerateFacelessVoiceOver($video->faceless),
            new TriggerMediaGeneration($video->faceless),
        ])->catch(function () use ($video) {
            (new DeleteVideoAction)->handle($video);
        })->dispatch();

        $video->load(['faceless.voice', 'faceless.music']);

        return $this->respondWithResource(VideoResource::make($video), Response::HTTP_ACCEPTED);
    }
}
