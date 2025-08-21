<?php

namespace App\Syllaby\Subscriptions\Actions;

use Exception;
use App\Syllaby\Users\User;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

class UnsubscribeUserAction
{
    public function __construct(private readonly ReleaseSchedulerAction $scheduler) {}

    /**
     * Handles the user subscription cancellation process.
     *
     * @throws Exception
     */
    public function handle(User $user): void
    {
        $subscription = $user->subscription();
        $subscription->setRelation('owner', $user->withoutRelations());

        if ($subscription->canceled()) {
            throw new Exception('User is not subscribed.');
        }

        if ($subscription->recurring()) {
            $this->scheduler->handle($subscription);
            $this->handleInvoices($subscription);
        }

        $subscription->cancel();
    }

    /**
     * Handles the user subscription invoices.
     */
    private function handleInvoices(Subscription $subscription): void
    {
        if (! $subscription->pastDue()) {
            return;
        }

        $invoices = Cashier::stripe()->invoices->all([
            'limit' => 10,
            'status' => 'open',
            'subscription' => $subscription->stripe_id,
        ]);

        if ($invoices->isEmpty()) {
            return;
        }

        foreach ($invoices->data as $invoice) {
            $invoice->markUncollectible();
        }
    }
}
