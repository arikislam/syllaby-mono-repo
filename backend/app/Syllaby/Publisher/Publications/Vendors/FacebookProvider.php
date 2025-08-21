<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\FacebookVideoData;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Concerns\ValidatesMetaMedia;
use App\Syllaby\Publisher\Publications\Concerns\PublishesFacebookMedia;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class FacebookProvider extends AbstractProvider
{
    use PublishesFacebookMedia, ValidatesMetaMedia;

    const int FILE_SIZE = 200 * 1024 * 1024; // 200MB

    public function publish(Publication $publication, SocialChannel $channel, PostType $type = PostType::POST): Publication
    {
        return match ($type) {
            PostType::REEL => $this->publishReel($publication, $channel),
            PostType::POST => $this->publishPost($publication, $channel),
            PostType::STORY => $this->publishStory($publication, $channel),
            default => throw new InvalidArgumentException("Un-supported post type: `{$type->toString()}`"),
        };
    }

    public function valid(Publication $publication, PostType $type = PostType::POST): bool
    {
        if (! $media = $publication->asset()) {
            return false;
        }

        return match ($type) {
            PostType::REEL => $this->isFacebookReel($media),
            PostType::POST => $this->isFacebookVideoPost($media) && $this->hasValidFacebookThumbnail($publication),
            PostType::STORY => $this->isFacebookStory($media),
            default => false
        };
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::Facebook;
    }

    public function format(array $data, SocialChannel $channel, PostType $type = PostType::POST): array
    {
        return [
            $channel->id => [
                'metadata' => FacebookVideoData::fromArray($data)->toArray(),
                'status' => Arr::has($data, 'scheduled_at') ? SocialUploadStatus::SCHEDULED->value : SocialUploadStatus::PROCESSING->value,
                'post_type' => $type->value,
            ],
        ];
    }

    /** @noinspection DuplicatedCode */
    public function prepare(Publication $publication, SocialChannel $channel, array $data, PostType $type = PostType::POST): Publication
    {
        if (! $this->factory->for($this->provider()->toString())->validate($channel)) {
            $channel->account()->update(['needs_reauth' => true]);
            throw new InvalidRefreshTokenException(__('publish.lost_permission'));
        }

        return tap($publication, fn (Publication $publication) => attempt(function () use ($type, $channel, $data, $publication) {
            $publication->update(['draft' => false, 'temporary' => false, 'scheduled' => Arr::has($data, 'scheduled_at')]);
            $publication->channels()->sync($this->format($data, $channel, $type), false);
        }));
    }
}
