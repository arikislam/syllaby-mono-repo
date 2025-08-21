<?php

namespace App\Http\Controllers\Api\v1\Events;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Planner\Actions\EventTrackerReportAction;

class TrackerReportController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Show events consistency tracker report for given interval.
     */
    public function show(Request $request, EventTrackerReportAction $tracker): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->startOfDay());
        $endDate = $request->query('end_date', now()->endOfMonth()->endOfDay());

        $report = $tracker->handle($this->user(), $startDate, $endDate);

        return $this->respondWithArray(Arr::only($report, ['total', 'completed', 'percentage']));
    }
}
