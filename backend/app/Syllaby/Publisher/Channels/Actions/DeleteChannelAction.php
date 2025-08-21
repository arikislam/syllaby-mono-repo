<?php

namespace App\Syllaby\Publisher\Channels\Actions;

use Exception;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Individual\Factory;

class DeleteChannelAction
{
    public function __construct(protected Factory $factory) {}

    public function handle(SocialChannel $channel, SocialAccountEnum $provider): bool
    {
        $channel->publications()->withCount('channels')->lazy()->each(
            fn (Publication $publication) => $this->prune($publication, $channel)
        );

        if ($this->hasMoreThanOneChannel($channel)) {
            return $channel->delete();
        }

        return tap(true, fn () => $this->invalidate($channel, $provider));
    }

    /**
     * @throws Exception
     */
    private function invalidate(SocialChannel $channel, SocialAccountEnum $provider): void
    {
        if (! $this->factory->for($provider->toString())->disconnect($channel)) {
            throw new Exception('Unable to disconnect account');
        }

        $channel->delete();
        $channel->account()->delete();
    }

    private function prune(Publication $publication, SocialChannel $channel): void
    {
        $publication->channels()->detach($channel->id);

        if ($publication->channels_count > 1) {
            return;
        }

        $publication->metrics()->delete();
        $publication->event()->delete();
        $publication->delete();
    }

    private function hasMoreThanOneChannel(SocialChannel $channel): bool
    {
        return $channel->account->channels()->count() > 1;
    }
}
