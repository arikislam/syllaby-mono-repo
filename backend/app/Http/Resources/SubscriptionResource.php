<?php

namespace App\Http\Resources;

use App\Syllaby\Users\User;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use App\Syllaby\Subscriptions\Enums\PlanType;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        /** @var SubscriptionContract $subscription */
        $subscription = $user->subscription();

        return [
            'plan' => [
                'id' => $user->plan->id,
                'name' => $user->plan->name,
                'stripe_id' => $user->plan->plan_id,
                'recurrence' => PlanType::from($user->plan->type)->label(),
                'amount' => [
                    'raw' => $user->plan->price,
                    'formatted' => Cashier::formatAmount($user->plan->price),
                ],
            ],

            'status' => [
                'ended' => $subscription->ended(),
                'subscribed' => $user->subscribed(),
                'on_trial' => $subscription->onTrial(),
                'label' => $subscription->status,
                'past_due' => $subscription->pastDue(),
                'cancelled' => $subscription->canceled(),
                'recurring' => $subscription->recurring(),
                'incomplete' => $subscription->incomplete(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'incomplete_payment' => $subscription->hasIncompletePayment(),
            ],

            'trial' => [
                'days' => (int) config('services.stripe.trial_days'),
                'can_extend' => $this->canExtendTrial($subscription),
                'ends_at' => $subscription->trial_ends_at?->toJSON(),
            ],

            'invoice' => $this->invoice($subscription),
            'downgrade_to_price' => $this->downgradeTo($subscription),

            'currency' => $user->plan->currency,
            'discounts' => $subscription instanceof \Laravel\Cashier\Subscription ? $subscription->discount() : null,

            'cancel_at' => $subscription->ends_at?->toJSON(),
            'created_at' => $subscription->created_at->toJSON(),
        ];
    }

    private function canExtendTrial(SubscriptionContract $subscription): bool
    {
        if (! $this->resource->usesStripe()) {
            return false;
        }

        if (! $subscription->onTrial()) {
            return false;
        }

        $trialEndDate = $subscription->trial_ends_at;
        $period = config('services.stripe.trial_days');
        $daysRemaining = now()->diffInDays($trialEndDate);

        return $daysRemaining <= $period;
    }

    private function invoice(SubscriptionContract $subscription): array
    {
        if (! $this->resource->usesStripe()) {
            return [];
        }

        if ($subscription->canceled()) {
            return [];
        }

        /** @var \Laravel\Cashier\Subscription $stripeSubscription */
        $stripeSubscription = $subscription;
        $invoice = $stripeSubscription->upcomingInvoice();

        return [
            'date' => $invoice->date()?->toJSON(),

            'status' => [
                'void' => $invoice->isVoid(),
                'paid' => $invoice->isPaid(),
                'draft' => $invoice->isDraft(),
            ],

            'amount' => [
                'raw' => $invoice->rawAmountDue(),
                'formatted' => $invoice->amountDue(),
            ],
        ];
    }

    private function downgradeTo(SubscriptionContract $subscription): ?string
    {
        if (! $this->resource->usesStripe()) {
            return null;
        }

        /** @var \Laravel\Cashier\Subscription $stripeSubscription */
        $schedulerId = $subscription->scheduler_id;

        if (blank($schedulerId)) {
            return null;
        }

        $scheduler = Cashier::stripe()->subscriptionSchedules->retrieve($schedulerId);

        return $scheduler->phases[1]->items[0]->price;
    }
}
