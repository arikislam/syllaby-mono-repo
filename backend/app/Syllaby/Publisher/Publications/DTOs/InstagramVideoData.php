<?php

namespace App\Syllaby\Publisher\Publications\DTOs;

use Arr;
use App\Syllaby\Publisher\Publications\Contracts\VideoDataContract;

class InstagramVideoData implements VideoDataContract
{
    public function __construct(
        public readonly ?string $caption,
        public ?string $video_id,
        public readonly bool $share_to_feed = true
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            caption: Arr::get($data, 'caption'),
            video_id: Arr::get($data, 'video_id'),
            share_to_feed: Arr::get($data, 'share_to_feed', true)
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function setVideoId(string $videoId): self
    {
        $this->video_id = $videoId;

        return $this;
    }
}