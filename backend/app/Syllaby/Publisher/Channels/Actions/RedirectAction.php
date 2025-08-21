<?php

namespace App\Syllaby\Publisher\Channels\Actions;

use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Individual\Factory;
use App\Syllaby\Publisher\Channels\Vendors\Individual\AbstractProvider;

class RedirectAction
{
    public function __construct(protected Factory $factory)
    {
    }

    public function handle(SocialAccountEnum $provider, string $redirectUrl): string
    {
        /** @var AbstractProvider $factory */
        $factory = $this->factory->for($provider->toString());

        return $factory->redirect($redirectUrl);
    }
}