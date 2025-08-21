<?php

namespace App\Syllaby\Publisher\Channels\Actions;

use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Business\Factory;

class StoreChannelAction
{
    public function __construct(protected Factory $factory) {}

    public function handle(SocialAccountEnum $provider, array $input): SocialAccount
    {
        return $this->factory->for($provider->toString())->save($input);
    }
}
