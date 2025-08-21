<?php

namespace App\Http\Controllers\Api\v1\Schedulers;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Schedulers\Occurrence;
use App\Http\Resources\OccurrenceResource;
use App\Http\Requests\Schedulers\OccurrenceScriptRequest;
use App\Syllaby\Schedulers\Actions\OccurrenceScriptAction;

class OccurrenceScriptController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Update the specified scheduler occurrence in storage.
     */
    public function update(OccurrenceScriptRequest $request, Occurrence $occurrence, OccurrenceScriptAction $writer): JsonResponse
    {
        try {
            $occurrence = $writer->handle($occurrence, $this->user(), $request->validated());
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(OccurrenceResource::make($occurrence));
    }
}
