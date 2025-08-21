<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Exception;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacelessResource;
use App\Syllaby\Videos\Actions\TranscribeAudioAction;
use App\Http\Requests\Generators\TranscribeAudioRequest;
use App\Syllaby\Credits\Actions\ChargeTranscriptionAction;

class FacelessTranscriptionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Initiates the transcription for a faceless video.
     */
    public function store(TranscribeAudioRequest $request, Faceless $faceless, TranscribeAudioAction $transcriber): JsonResponse
    {
        try {
            $faceless = $transcriber->handle($faceless, [
                'url' => $request->audio->getFullUrl(),
            ]);

            app(ChargeTranscriptionAction::class)->handle($this->user(), $request->audio);
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(FacelessResource::make($faceless));
    }
}
