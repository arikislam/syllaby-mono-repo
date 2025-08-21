<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Exception;
use Throwable;
use Stripe\StripeObject;
use App\Syllaby\Users\User;
use Laravel\Cashier\Cashier;
use Illuminate\Http\JsonResponse;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Controllers\Controller;
use Laravel\Cashier\SubscriptionBuilder;
use App\Http\Resources\RedirectUrlResource;
use App\Http\Requests\Subscriptions\CheckoutRequest;

class CheckoutController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @throws Exception
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $user = $this->user();
        $plan = Plan::active()->recurring()->findOrFail($request->input('plan_id'));

        $url = match (true) {
            filled($user->pm_exemption_code) => $this->exemptPaymentMethod($user, $plan, $request),
            default => $this->checkout($user, $plan, $request),
        };

        return $this->respondWithResource(new RedirectUrlResource($url));
    }

    /**
     * Subscribes user to plan without payment method upfront.
     */
    private function exemptPaymentMethod(User $user, Plan $plan, CheckoutRequest $request): string
    {
        $days = $plan->trialDays($user);
        $checkout = (new SubscriptionBuilder($user, 'default', $plan->plan_id))->trialUntil(
            now()->addDays($days)->endOfDay()
        );

        return tap($request->input('success_url'), fn () => $checkout->create());
    }

    /**
     * Creates a checkout session for the user.
     */
    private function checkout(User $user, Plan $plan, CheckoutRequest $request): string
    {
        $days = $plan->trialDays($user);
        $checkout = (new SubscriptionBuilder($user, 'default', $plan->plan_id))->trialUntil(
            now()->addDays($days)->endOfDay()
        );

        if ($promo = $this->fetchPromotion($user->promo_code)) {
            $checkout = $checkout->withPromotionCode($promo->id);
        } else {
            $checkout = $checkout->allowPromotionCodes();
        }

        $session = $checkout->checkout([
            'client_reference_id' => $user->id,
            'customer_update' => ['name' => 'auto', 'address' => 'auto'],
            'consent_collection' => ['terms_of_service' => 'required'],
            'custom_text' => [
                'terms_of_service_acceptance' => [
                    'message' => $this->consent(),
                ],
            ],
            'cancel_url' => $request->input('cancel_url'),
            'success_url' => $request->input('success_url'),
        ]);

        return $session->url;
    }

    /**
     * Attempts to fetch the promotional code from Stripe.
     */
    private function fetchPromotion(?string $code): ?StripeObject
    {
        if (blank($code)) {
            return null;
        }

        try {
            return Cashier::stripe()->promotionCodes->all([
                'limit' => 1, 'active' => true, 'code' => $code,
            ])->first();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Custom consent text for stripe checkout.
     */
    private function consent(): string
    {
        return <<<'EOT'
            By checking this box, you confirm that you've read and understood the terms regarding 
            the subscription charges, cancellation policy, and applicable fees as per the 
            Syllaby [Terms of Service](https://syllaby.io/terms-and-conditions). To avoid subscription 
            fees, you must cancel within the trial period. If you use the services beyond the trial period, 
            no refund will be issued.
        EOT;
    }
}
