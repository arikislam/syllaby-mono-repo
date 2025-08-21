<?php

namespace App\Syllaby\Publisher\Channels\Extensions;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CustomThreadsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('threads', CustomThreadsProvider::class);
    }
}
