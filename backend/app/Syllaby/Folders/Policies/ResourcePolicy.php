<?php

namespace App\Syllaby\Folders\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Resource;

class ResourcePolicy
{
    public function move(User $user, Resource $resource): bool
    {
        return $user->owns($resource);
    }

    public function view(User $user, Resource $resource): bool
    {
        return $user->owns($resource);
    }
}
