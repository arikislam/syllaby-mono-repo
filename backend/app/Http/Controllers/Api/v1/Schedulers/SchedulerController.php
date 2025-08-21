<?php

namespace App\Http\Controllers\Api\v1\Schedulers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Schedulers\Scheduler;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\SchedulerResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Schedulers\CreateSchedulerRequest;
use App\Http\Requests\Schedulers\UpdateSchedulerRequest;
use App\Syllaby\Schedulers\Actions\CreateSchedulerAction;
use App\Syllaby\Schedulers\Actions\UpdateSchedulerAction;

class SchedulerController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->only(['store', 'update']);
    }

    /**
     * Display a listing of the scheduler.
     */
    public function index(): JsonResponse
    {
        $user = $this->user();

        $schedulers = QueryBuilder::for(Scheduler::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
            ])
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate($this->take());

        return $this->respondWithPagination(SchedulerResource::collection($schedulers));
    }

    /**
     * Display the specified scheduler.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $includes = array_filter(explode(',', $request->query('include')));

        $scheduler = QueryBuilder::for(Scheduler::class)
            ->where('user_id', $this->user()->id)
            ->allowedIncludes($includes)
            ->find($id);

        if (! $scheduler) {
            return $this->respondWithArray(null, Response::HTTP_NOT_FOUND, 'Scheduler not found.');
        }

        return $this->respondWithResource(SchedulerResource::make($scheduler));
    }

    /**
     * Store a newly created scheduler in storage.
     */
    public function store(CreateSchedulerRequest $request, CreateSchedulerAction $create): JsonResponse
    {
        $user = $this->user();
        $scheduler = $create->handle($user, $request->validated());

        return $this->respondWithResource(SchedulerResource::make($scheduler), Response::HTTP_CREATED);
    }

    /**
     * Update the specified scheduler in storage.
     */
    public function update(UpdateSchedulerRequest $request, Scheduler $scheduler, UpdateSchedulerAction $update): JsonResponse
    {
        try {
            $scheduler = $update->handle($scheduler, $request->validated());
        } catch (Exception) {
            return $this->errorInternalError('Failed to update scheduler.');
        }

        return $this->respondWithResource(SchedulerResource::make($scheduler));
    }
}
