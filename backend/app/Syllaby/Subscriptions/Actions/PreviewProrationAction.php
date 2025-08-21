<?php

namespace App\Syllaby\Subscriptions\Actions;

use Stripe\Invoice;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;

class PreviewProrationAction
{
    /**
     * Preview the proration for a subscription change.
     */
    public function handle(User $user, array $input): array
    {
        $subscription = $user->subscription();
        $subscription->setRelation('owner', $user->withoutRelations());

        $recurrence = $user->plan->type === 'month' ? 'monthly' : 'yearly';

        $quantity = Arr::get($input, 'quantity', 10);
        $invoice = $this->previewFromInvoice($user, $subscription, $quantity, $recurrence);

        $amount = collect($invoice->lines->data)->where('type', 'invoiceitem')
            ->where('proration', true)
            ->sum('amount');

        return [
            'quantity' => $quantity,
            'recurrence' => $recurrence,
            'next_billing_date' => [
                'timestamp' => $invoice->next_payment_attempt,
                'formatted' => Carbon::createFromTimestamp($invoice->next_payment_attempt)->toJson(),
            ],
            'amount' => [
                'raw' => $amount,
                'formatted' => Cashier::formatAmount($amount, $invoice->currency),
            ],
        ];
    }

    /**
     * Preview the proration for a subscription change.
     */
    protected function previewFromInvoice(User $user, Subscription $subscription, int $quantity, string $recurrence): Invoice
    {
        $priceId = config("services.stripe.add_ons.storage.{$recurrence}");

        $items = $subscription->items->pluck('stripe_price', 'stripe_id');
        $itemId = Arr::get($items->flip(), $priceId);

        return Cashier::stripe()->invoices->upcoming([
            'customer' => $user->stripe_id,
            'subscription' => $subscription->stripe_id,
            'subscription_items' => [
                [
                    $itemId ? 'id' : 'price' => $itemId ?? $priceId,
                    'quantity' => $quantity,
                ],
            ],
            'subscription_proration_date' => now()->timestamp,
        ]);
    }
}
