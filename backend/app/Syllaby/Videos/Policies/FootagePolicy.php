<?php

namespace App\Syllaby\Videos\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Footage;
use Illuminate\Auth\Access\Response;

class FootagePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Footage $footage): bool
    {
        return $user->owns($footage);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Footage $footage): Response
    {
        return match (true) {
            !$user->owns($footage) => Response::deny("You don't have permission to edit this video"),
            $footage->video->isBusy() => Response::deny('Video is currently being exported'),
            default => Response::allow()
        };
    }
}
