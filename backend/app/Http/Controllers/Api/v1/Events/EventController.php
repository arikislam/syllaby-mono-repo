<?php

namespace App\Http\Controllers\Api\v1\Events;

use Throwable;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use App\Syllaby\Planner\Event;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Syllaby\Planner\Filters\DateRangeFilter;
use App\Syllaby\Planner\Actions\UpdateEventAction;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Actions\DeletePublicationAction;

class EventController extends Controller
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
     * List all events for the given date range.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->ownedBy($this->user());
        $includes = array_filter(explode(',', $request->query('include')));

        $events = QueryBuilder::for($query)->allowedFilters([
            AllowedFilter::exact('scheduler', 'scheduler_id'),
            AllowedFilter::exact('type', 'model_type')->default('video'),
            AllowedFilter::custom('date', new DateRangeFilter, 'starts_at'),
        ])
            ->allowedIncludes($includes)
            ->orderBy('starts_at', 'desc')
            ->get();

        return $this->respondWithResource(EventResource::collection($events));
    }

    /**
     * Updates the given event details in storage
     */
    public function update(Event $event, UpdateEventRequest $request, UpdateEventAction $update): JsonResponse
    {
        if (Feature::inactive('calendar')) {
            return $this->errorUnsupportedFeature();
        }

        if (! $event = $update->handle($event, $request->validated())) {
            return $this->errorInternalError('Whoop! It was not possible to edit the event.');
        }

        return $this->respondWithResource(EventResource::make($event));
    }

    /**
     * Deletes a given event.
     *
     * @throws Throwable
     */
    public function destroy(Event $event): Response|JsonResponse
    {
        if (Feature::inactive('calendar')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('delete', $event);

        if (morph_type($event->model_type, Publication::class)) {
            app(DeletePublicationAction::class)->handle($event->model);

            return response()->noContent();
        }

        attempt(function () use ($event) {
            $event->model()->delete();
            $event->delete();
        });

        return response()->noContent();
    }
}
