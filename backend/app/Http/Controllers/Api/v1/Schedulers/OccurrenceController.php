<?php

namespace App\Http\Controllers\Api\v1\Schedulers;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Schedulers\Scheduler;
use Spatie\QueryBuilder\QueryBuilder;
use App\Syllaby\Schedulers\Occurrence;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\SchedulerResource;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\OccurrenceResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Schedulers\CreateOccurrenceRequest;
use App\Http\Requests\Schedulers\UpdateOccurrenceRequest;
use App\Syllaby\Schedulers\Actions\CreateOccurrenceAction;
use App\Syllaby\Schedulers\Actions\UpdateOccurrenceAction;

class OccurrenceController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except('index');
    }

    /**
     * Display a listing of the scheduler occurrences.
     */
    public function index(Scheduler $scheduler): JsonResponse
    {
        $occurrences = QueryBuilder::for(Occurrence::class)
            ->allowedFilters([
                AllowedFilter::callback('date', fn (Builder $query, $value) => $query->whereDate('occurs_at', $value)),
            ])
            ->where('scheduler_id', $scheduler->id)
            ->where('user_id', $scheduler->user_id)
            ->get();

        return $this->respondWithResource(OccurrenceResource::collection($occurrences));
    }

    /**
     * Store a newly created scheduler occurrence in storage.
     */
    public function store(CreateOccurrenceRequest $request, Scheduler $scheduler, CreateOccurrenceAction $create): JsonResponse
    {
        $scheduler = $create->handle($scheduler, $request->validated());

        return $this->respondWithResource(SchedulerResource::make($scheduler), Response::HTTP_ACCEPTED);
    }

    /**
     * Update the specified scheduler occurrence in storage.
     */
    public function update(UpdateOccurrenceRequest $request, Occurrence $occurrence, UpdateOccurrenceAction $update): JsonResponse
    {
        try {
            $occurrence = $update->handle($occurrence, $request->validated());
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(OccurrenceResource::make($occurrence));
    }
}
