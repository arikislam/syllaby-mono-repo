<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use Arr;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Individual\Factory;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

abstract class AbstractProvider
{
    public function __construct(protected Factory $factory) {}

    /**
     * Publish the publication to the channel.
     *
     * @throws PublicationFailedException
     */
    abstract public function publish(Publication $publication, SocialChannel $channel, PostType $type = PostType::POST): Publication;

    /**
     * Check if the platform can handle the publication.
     */
    abstract public function valid(Publication $publication, PostType $type = PostType::POST): bool;

    /**
     * The name of the provider.
     */
    abstract public function provider(): SocialAccountEnum;

    /**
     * Format the data for the channel.
     */
    abstract public function format(array $data, SocialChannel $channel, PostType $type = PostType::POST): array;

    /**
     * Prepare the publication for publishing.
     *
     * @throws InvalidRefreshTokenException
     */
    public function prepare(Publication $publication, SocialChannel $channel, array $data, PostType $type = PostType::POST): Publication
    {
        if (! $this->factory->for($this->provider()->toString())->validate($channel)) {
            $this->factory->for($this->provider()->toString())->refresh($channel->account);
        }

        return tap($publication, function (Publication $publication) use ($channel, $data, $type) {
            $publication->update(['draft' => false, 'temporary' => false, 'scheduled' => Arr::has($data, 'scheduled_at')]);
            $publication->channels()->sync($this->format($data, $channel, $type), false);
        });
    }
}
