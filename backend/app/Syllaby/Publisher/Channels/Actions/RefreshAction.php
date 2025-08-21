<?php

namespace App\Syllaby\Publisher\Channels\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Individual\Factory;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class RefreshAction
{
    public function __construct(protected Factory $factory) {}

    /** @throws InvalidRefreshTokenException */
    public function handle(array $input, SocialAccountEnum $provider): SocialAccount
    {
        $channel = SocialChannel::query()->find(Arr::get($input, 'id'));

        return $this->factory->for($provider->toString())->refresh($channel->account);
    }
}
