<?php

namespace App\Syllaby\Planner\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Planner\Event;

class EventPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->owns($event);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->owns($event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->owns($event);
    }
}
