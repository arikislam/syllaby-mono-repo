<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Illuminate\Validation\ValidationException;

class ResumeSubscriptionAction
{
    /**
     * Resume the canceled user subscription.
     */
    public function handle(User $user): void
    {
        $subscription = tap($user->subscription(), function ($subscription) {
            $subscription->load('owner');
        });

        if (! $subscription) {
            $this->fail('This account does not have an active subscription.');

            return;
        }

        if (! $subscription->onGracePeriod()) {
            $this->fail('This subscription has expired and cannot be resumed. Please create a new subscription.');

            return;
        }

        $subscription->resume();
    }

    /**
     * Fails to resume subscruption with a reason.
     */
    private function fail(string $reason): void
    {
        throw ValidationException::withMessages(['*' => $reason]);
    }
}
