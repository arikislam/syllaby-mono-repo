<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Laravel\Cashier\Cashier;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class PaymentIntentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Handles the creation of a Stripe ephemeral key, setup intent, and associated customer details.
     */
    public function store(): JsonResponse
    {
        $user = $this->user();
        $customer = $user->createOrGetStripeCustomer();

        $key = Cashier::stripe()->ephemeralKeys->create(
            ['customer' => $customer->id],
            ['stripe_version' => Cashier::STRIPE_VERSION]
        );

        return $this->respondWithArray([
            'ephemeral_key' => $key->secret,
            'customer_id' => $user->stripe_id,
            'client_secret' => $user->createSetupIntent()->client_secret,
        ]);
    }
}
