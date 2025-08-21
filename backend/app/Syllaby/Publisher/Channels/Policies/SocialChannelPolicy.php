<?php

namespace App\Syllaby\Publisher\Channels\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Publisher\Channels\SocialChannel;

class SocialChannelPolicy
{
    public function view(User $user, SocialChannel $channel): bool
    {
        return $user->owns($channel->account);
    }

    public function update(User $user, SocialChannel $channel): bool
    {
        return $user->owns($channel->account);
    }

    public function delete(User $user, SocialChannel $channel): bool
    {
        return $user->owns($channel->account);
    }
}
