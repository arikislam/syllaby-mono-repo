<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Stripe\Exception\ApiErrorException;
use App\Syllaby\Subscriptions\Actions\UnsubscribeUserAction;

class SubscriptionCancelController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Cancel the user's subscription.
     */
    public function destroy(UnsubscribeUserAction $unsubscribe): Response|JsonResponse
    {
        $user = $this->user();

        try {
            $unsubscribe->handle($user);
        } catch (ApiErrorException $exception) {
            Log::error('Stripe API error during cancellation', ['message' => $exception->getMessage()]);

            return $this->errorInternalError("We couldn't process your cancellation. Please try again or contact support");
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return response()->noContent();
    }
}
