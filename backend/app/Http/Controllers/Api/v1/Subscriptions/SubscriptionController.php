<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Throwable;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;

class SubscriptionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Handles subscription management, including creation and retrieval.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $user = $this->user();

        if ($user->onTrial()) {
            return $this->errorForbidden('You are currently on your trial period.');
        }

        if ($user->subscribed()) {
            return $this->errorForbidden('You are already subscribed.');
        }

        $plan = Plan::active()->recurring()->findOrFail($validated['plan_id']);

        try {
            $intent = Cashier::stripe()->setupIntents->retrieve($validated['payment_method']);
            $user->updateDefaultPaymentMethod($intent->payment_method);

            $user->newSubscription('default', $plan->plan_id)->create($intent->payment_method);
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithMessage('Subscription created successfully.');
    }

    /**
     * Display the subscription details.
     */
    public function show(): JsonResponse
    {
        $user = $this->user();
        $user->loadMissing(['plan', 'subscriptions.owner']);

        if (! $user->plan) {
            return $this->respondWithArray(null);
        }

        if (! $user->subscription()) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithResource(SubscriptionResource::make($user));
    }
}
