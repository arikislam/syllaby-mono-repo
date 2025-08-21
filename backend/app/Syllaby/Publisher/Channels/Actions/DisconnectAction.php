<?php

namespace App\Syllaby\Publisher\Channels\Actions;

use Arr;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class DisconnectAction
{
    public function __construct(protected DeleteChannelAction $action)
    {
    }

    public function handle(array $input, SocialAccountEnum $provider): bool
    {
        return attempt(function () use ($input, $provider) {
            $channel = SocialChannel::query()->find(Arr::get($input, 'id'));
            return $this->action->handle($channel, $provider);
        });
    }
}