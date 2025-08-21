<?php

namespace App\Syllaby\Publisher\Channels\Contracts;

use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

interface ChannelContract
{
    /**
     * Get a list of channels for a provider
     */
    public function get();

    /**
     * Connect the channel to the account.
     */
    public function save(array $input);

    /**
     * Name of the provider
     */
    public function provider(): SocialAccountEnum;

    /**
     * Time to live for the cache
     */
    public function ttl(): int;
}