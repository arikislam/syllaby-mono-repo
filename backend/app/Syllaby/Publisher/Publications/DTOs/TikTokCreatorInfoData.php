<?php

namespace App\Syllaby\Publisher\Publications\DTOs;

use Arr;
use Illuminate\Contracts\Support\Arrayable;

readonly class TikTokCreatorInfoData implements Arrayable
{
    public function __construct(
        public string $creator_avatar_url,
        public string $creator_username,
        public string $creator_nickname,
        public array $privacy_level_options,
        public bool $comment_disabled,
        public bool $duet_disabled,
        public bool $stitch_disabled,
        public int $max_video_post_duration_sec,
    ) {}

    public static function fromResponse(array $response): static
    {
        return new static(
            creator_avatar_url: Arr::get($response, 'data.creator_avatar_url'),
            creator_username: Arr::get($response, 'data.creator_username'),
            creator_nickname: Arr::get($response, 'data.creator_nickname'),
            privacy_level_options: Arr::get($response, 'data.privacy_level_options'),
            comment_disabled: Arr::get($response, 'data.comment_disabled'),
            duet_disabled: Arr::get($response, 'data.duet_disabled'),
            stitch_disabled: Arr::get($response, 'data.stitch_disabled'),
            max_video_post_duration_sec: Arr::get($response, 'data.max_video_post_duration_sec'),
        );
    }

    public static function defaults(): static
    {
        return new static(
            creator_avatar_url: '',
            creator_username: '',
            creator_nickname: '',
            privacy_level_options: ['PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'SELF_ONLY'],
            comment_disabled: false,
            duet_disabled: false,
            stitch_disabled: false,
            max_video_post_duration_sec: 600,
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}
