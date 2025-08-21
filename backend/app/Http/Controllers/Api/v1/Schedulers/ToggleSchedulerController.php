<?php

namespace App\Http\Controllers\Api\v1\Schedulers;

use App\Http\Controllers\Controller;
use App\Syllaby\Schedulers\Scheduler;
use App\Http\Resources\SchedulerResource;
use App\Syllaby\Schedulers\Actions\ToggleSchedulerAction;

class ToggleSchedulerController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Toggle the scheduler.
     */
    public function update(Scheduler $scheduler, ToggleSchedulerAction $toggle)
    {
        $this->authorize('toggle', $scheduler);

        $scheduler = $toggle->handle($scheduler);

        return $this->respondWithResource(SchedulerResource::make($scheduler));
    }
}
