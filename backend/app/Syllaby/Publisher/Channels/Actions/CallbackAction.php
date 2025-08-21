<?php

namespace App\Syllaby\Publisher\Channels\Actions;

use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Individual\Factory;

class CallbackAction
{
    public function __construct(protected Factory $factory)
    {
    }

    public function handle(SocialAccountEnum $provider): SocialAccount
    {
        return $this->factory->for($provider->toString())->callback();
    }
}