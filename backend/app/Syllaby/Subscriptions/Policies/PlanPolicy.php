<?php

namespace App\Syllaby\Subscriptions\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Users\Actions\CalculateStorageAction;

class PlanPolicy
{
    /**
     * Determine whether the user can manage storage.
     */
    public function storage(User $user, Plan $plan, int $quantity): bool
    {
        if ($user->onTrial()) {
            return false;
        }

        $base = (int) $plan->details('features.storage');

        $quantity = $quantity * 1024 * 1024 * 1024;
        $used = app(CalculateStorageAction::class)->handle($user);

        return ($base + $quantity) >= $used;
    }
}
