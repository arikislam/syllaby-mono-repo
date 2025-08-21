<?php

namespace App\Http\Controllers\Api\v1\User;

use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Publisher\Publications\Actions\TotalMonthlySchedulesAction;

class UserPublicationUsageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display authenticated user currently used and total amount of storage.
     */
    public function show(TotalMonthlySchedulesAction $schedules): JsonResponse
    {
        $user = $this->user();
        $subscribed = $user->subscribed();

        $used = $subscribed ? $schedules->handle($user) : 0;
        $total = (int) Feature::value('max_scheduled_posts');

        return $this->respondWithArray(['used' => $used, 'total' => $total]);
    }
}
