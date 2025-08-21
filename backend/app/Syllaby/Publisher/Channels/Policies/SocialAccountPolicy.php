<?php

namespace App\Syllaby\Publisher\Channels\Policies;

use App\Syllaby\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Syllaby\Publisher\Channels\SocialAccount;

class SocialAccountPolicy
{
    use HandlesAuthorization;

    public function update(User $user, SocialAccount $socialAccount): bool
    {
        return $user->owns($socialAccount);
    }

    public function delete(User $user, SocialAccount $socialAccount): bool
    {
        return $user->owns($socialAccount);
    }
}
