<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Footage;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Videos\UpdateTimelineRequest;
use App\Syllaby\Videos\Actions\UpdateTimelineAction;

class TimelineController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected UpdateTimelineAction $update)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except('index');
    }

    /**
     * Display a list of timeline elements.
     */
    public function index(Footage $footage): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('view', $footage);
        $elements = Arr::get($footage->timeline->content, 'elements');

        return $this->respondWithArray($elements);
    }

    /**
     * Bulk updates all timeline elements in storage.
     */
    public function update(Footage $footage, UpdateTimelineRequest $request, UpdateTimelineAction $update): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $elements = $request->input('elements');
        $source = array_merge($footage->timeline->content, ['elements' => $elements]);

        $update->handle($footage->timeline, $source);

        return $this->respondWithArray($elements);
    }
}
