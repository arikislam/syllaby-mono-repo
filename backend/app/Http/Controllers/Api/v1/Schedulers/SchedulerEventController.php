<?php

namespace App\Http\Controllers\Api\v1\Schedulers;

use Laravel\Pennant\Feature;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use App\Syllaby\Publisher\Publications\Actions\DeletePublicationAction;

class SchedulerEventController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Remove all events associated with the given scheduler.
     */
    public function destroy(Scheduler $scheduler, DeletePublicationAction $remover): Response|JsonResponse
    {
        if (Feature::inactive('calendar')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('view', $scheduler);

        DB::transaction(function () use ($scheduler, $remover) {
            $scheduler->load('events.model');
            $scheduler->events->each(fn ($event) => $remover->handle($event->model));
            $scheduler->update(['status' => SchedulerStatus::DELETED]);
        });

        return response()->noContent();
    }
}
