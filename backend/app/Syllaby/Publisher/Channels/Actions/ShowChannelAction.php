<?php

namespace App\Syllaby\Publisher\Channels\Actions;

use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Business\Factory;

class ShowChannelAction
{
    public function __construct(protected Factory $factory)
    {
    }

    public function handle(SocialAccountEnum $provider)
    {
        return $this->factory->for($provider->toString())->get();
    }
}