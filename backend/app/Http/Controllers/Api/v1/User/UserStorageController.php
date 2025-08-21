<?php

namespace App\Http\Controllers\Api\v1\User;

use Number;
use App\Syllaby\Users\User;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Users\Actions\CalculateStorageAction;
use App\Syllaby\Users\Actions\CalculateStorageBreakdownAction;

class UserStorageController extends Controller
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
     *
     * Optionally include storage breakdown with ?include=breakdown
     */
    public function show(Request $request, CalculateStorageAction $storage, CalculateStorageBreakdownAction $breakdown): JsonResponse
    {
        $user = $this->user();
        $plan = $user->plan;
        $base = $used = $extra = 0;

        if ($user->subscribed() && filled($plan)) {
            $base = $plan->details('features.storage');
            $used = $storage->handle($user);
            $extra = $this->extraStorage($user, $plan);
        }

        $total = (int) Feature::for($user)->value('max_storage') ?? 0;

        $response = [
            'used' => ['raw' => $used, 'formatted' => Number::fileSize($used)],
            'total' => ['raw' => $total, 'formatted' => Number::fileSize($total)],
            'base' => ['raw' => $base, 'formatted' => Number::fileSize($base)],
            'extra' => ['raw' => $extra, 'formatted' => Number::fileSize($extra)],
        ];

        $includes = array_filter(explode(',', $request->query('include', '')));

        if (in_array('breakdown', $includes)) {
            $available = max(0, $total - $used);

            $response['usage_percentage'] = $total > 0 ? round(($used / $total) * 100, 2) : 0;
            $response['available'] = ['raw' => $available, 'formatted' => Number::fileSize($available)];
            $response['breakdown'] = Cache::flexible("user:{$user->id}:storage:breakdown", [300, 180], fn () => $breakdown->handle($user));
        }

        return $this->respondWithArray($response);
    }

    /**
     * Get the extra storage for the user.
     */
    protected function extraStorage(User $user, Plan $plan): int
    {
        // Google Play and JVZoo subscriptions don't support storage add-ons atm
        if (! $user->usesStripe()) {
            return 0;
        }

        $recurrence = $plan->type === 'month' ? 'monthly' : 'yearly';

        $id = config("services.stripe.add_ons.storage.{$recurrence}");

        if (! $storage = $user->subscription()->items()->where('stripe_price', $id)->first()) {
            return 0;
        }

        return $storage->quantity * 1024 * 1024 * 1024;
    }
}
