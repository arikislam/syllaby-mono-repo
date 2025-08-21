<?php

namespace App\Syllaby\RealClones\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\RealClones\Avatar;

class AvatarPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Avatar $avatar): bool
    {
        return $user->owns($avatar) && $avatar->type === Avatar::PHOTO;
    }
}
