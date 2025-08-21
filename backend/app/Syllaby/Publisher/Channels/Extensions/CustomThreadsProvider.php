<?php

namespace App\Syllaby\Publisher\Channels\Extensions;

use Override;
use SocialiteProviders\Threads\Provider;
use SocialiteProviders\Manager\OAuth2\User;

class CustomThreadsProvider extends Provider
{
    /**
     * The reason for overriding this class is to have null as fallback
     * for threads_profile_picture_url in case it's not available.
     * If profile picture is not available, the API won't send
     * the key in the response, and it will throw an error.
     */
    #[Override]
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['username'],
            'name' => null,
            'email' => null,
            'avatar' => $user['threads_profile_picture_url'] ?? null,
        ]);
    }
}
