<?php

namespace App\Http\Controllers\Api\v1\Schedulers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Schedulers\Scheduler;
use App\Http\Resources\SchedulerResource;
use App\Http\Requests\Schedulers\RunSchedulerRequest;
use App\Syllaby\Schedulers\Actions\RunSchedulerAction;

class RunSchedulerController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Start the specified scheduler.
     */
    public function update(RunSchedulerRequest $request, Scheduler $scheduler, RunSchedulerAction $run): JsonResponse
    {
        $scheduler = $run->handle($scheduler, $this->user(), $request->validated());

        return $this->respondWithResource(SchedulerResource::make($scheduler));
    }
}
