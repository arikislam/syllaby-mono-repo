<?php

namespace App\Http\Controllers\Api\v1\Events;

use Laravel\Pennant\Feature;
use App\Syllaby\Planner\Event;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;

class EventCompleteController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Toggles the  complete status for the given event.
     */
    public function update(Event $event): JsonResponse
    {
        if (Feature::inactive('calendar')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('update', $event);

        $event = tap($event)->update([
            'completed_at' => blank($event->completed_at) ? now() : null,
        ]);

        return $this->respondWithResource(EventResource::make($event));
    }
}
