<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;

class ExtendTrialAction
{
    /**
     * Handles the process of extending the user trial.
     */
    public function handle(User $user, int $days = 7): bool
    {
        if (! $this->canExtendTrialFor($user, $days)) {
            return false;
        }

        $this->extendTrial($user, $days);

        return true;
    }

    /**
     * Extends the user trial.
     */
    public function extendTrial(User $user, int $days): void
    {
        $subscription = $user->subscription();

        $subscription->extendTrial(
            $subscription->trial_ends_at->addDays($days)
        );
    }

    /**
     * Checks whether the user can extend the current trial period.
     */
    private function canExtendTrialFor(User $user, int $days): bool
    {
        return $user->subscribed() && $user->onTrial() && $this->trialEndsInLess($user, $days);
    }

    /**
     * Checks the remaining trial days are less the double the allowed one.
     */
    private function trialEndsInLess(User $user, int $days): bool
    {
        $trialEndDate = $user->subscription()->trial_ends_at;
        $daysRemaining = ceil(now()->diffInDays($trialEndDate));

        return $daysRemaining <= $days;
    }
}
