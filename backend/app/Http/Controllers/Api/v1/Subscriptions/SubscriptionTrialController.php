<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Subscriptions\Actions\ExtendTrialAction;
use App\Syllaby\Subscriptions\Notifications\TrialExtended;

class SubscriptionTrialController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(): RedirectResponse
    {
        $user = $this->user();
        $subscription = $user->subscription();

        if ($user->onTrial()) {
            $subscription->endTrial();
        }

        return $user->redirectToBillingPortal(
            config('app.frontend_url').'/subscriptions'
        );
    }

    /**
     * Extends the authenticated user current trial period.
     */
    public function update(ExtendTrialAction $trial): JsonResponse
    {
        $user = $this->user();
        $days = config('services.stripe.trial_days');

        if (! $trial->handle($user, $days)) {
            return $this->respondWithMessage("Whoops! Seems like your trial can't be extended.", Response::HTTP_BAD_REQUEST);
        }

        $user->notify(new TrialExtended);

        return $this->respondWithMessage("Your trial was extended for more {$days} days.");
    }
}
