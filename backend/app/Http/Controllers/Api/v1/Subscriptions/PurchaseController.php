<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use App\Http\Resources\RedirectUrlResource;
use App\Http\Requests\Subscriptions\CreatePurchaseRequest;

class PurchaseController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Create Stripe checkout sessions for one time in-app product purchases.
     */
    public function store(CreatePurchaseRequest $request): JsonResponse
    {
        $user = $this->user();

        if (!$product = Plan::active()->oneTime()->find($request->input('price_id'))) {
            return $this->errorNotFound('Whoops! The product does not exist.');
        }

        $session = $user->checkout($product->plan_id, [
            'client_reference_id' => $user->id,
            'cancel_url' => $request->input('cancel_url'),
            'success_url' => $request->input('success_url'),
            'allow_promotion_codes' => true,
            'consent_collection' => ['terms_of_service' => 'required'],
            'metadata' => [
                'price_id' => $product->plan_id,
                ...$request->input('context', []),
                'action' => Arr::get($product->meta, 'type', 'extra-credits'),
            ],
        ]);

        return $this->respondWithResource(new RedirectUrlResource($session->url));
    }
}
