<?php

namespace App\Syllaby\Subscriptions\Actions;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Laravel\Cashier\Cashier;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\Subscription;
use Illuminate\Validation\ValidationException;

class SwapPlanAction
{
    /**
     * Create a new SwapPlanAction instance.
     */
    public function __construct(protected ReleaseSchedulerAction $scheduler) {}

    /**
     * Allows users to swap plans.
     */
    public function handle(User $user, array $input): bool
    {
        $current = $user->plan;
        $intended = Plan::active()->find(Arr::get($input, 'plan_id'));

        /** @var Subscription $subscription */
        $subscription = $user->subscription('default');
        $subscription->setRelation('owner', $user->withoutRelations());

        try {
            if ($user->onTrial()) {
                $subscription->endTrial()->findItemOrFail($current->plan_id)->swapAndInvoice($intended->plan_id);

                return true;
            }

            $this->scheduler->handle($subscription);

            if ($this->isUpgrade($current, $intended)) {
                $subscription->findItemOrFail($current->plan_id)->swapAndInvoice($intended->plan_id);
            } else {
                $this->scheduleDowngrade($subscription, $current, $intended);
            }

        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['*' => $exception->getMessage()]);
        }

        return true;
    }

    /**
     * Check if the plan is an upgrade.
     */
    protected function isUpgrade(Plan $current, Plan $intended): bool
    {
        return $intended->price > $current->price;
    }

    /**
     * Schedule a downgrade.
     */
    private function scheduleDowngrade(Subscription $subscription, Plan $current, Plan $intended): void
    {
        $schedule = Cashier::stripe()->subscriptionSchedules->create([
            'from_subscription' => $subscription->stripe_id,
        ]);

        $items = collect($schedule->phases[0]->items)->map(fn ($item) => [
            'price' => $item->price,
            'quantity' => $item->quantity,
        ]);

        $updated = $items->map(fn ($item) => match (Arr::get($item, 'price')) {
            $current->plan_id => ['price' => $intended->plan_id, 'quantity' => 1],
            default => $item,
        });

        $schedule = Cashier::stripe()->subscriptionSchedules->update($schedule->id, [
            'end_behavior' => 'release',
            'proration_behavior' => 'none',
            'phases' => [
                [
                    'start_date' => $schedule->phases[0]->start_date,
                    'end_date' => $schedule->phases[0]->end_date,
                    'items' => $items->toArray(),
                ],
                [
                    'iterations' => 1,
                    'proration_behavior' => 'none',
                    'billing_cycle_anchor' => 'automatic',
                    'description' => "Plan Downgrade to {$intended->name}",
                    'collection_method' => 'charge_automatically',
                    'items' => $updated->toArray(),
                ],
            ],
        ]);

        $subscription->update(['scheduler_id' => $schedule->id]);
    }
}
