<?php

namespace App\Syllaby\Schedulers\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Schedulers\Occurrence;

class OccurrencePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Occurrence $occurrence): bool
    {
        return $user->owns($occurrence);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Occurrence $occurrence): bool
    {
        return $user->owns($occurrence);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Occurrence $occurrence): bool
    {
        return $user->owns($occurrence);
    }
}
