<?php

namespace App\Syllaby\Publisher\Publications\DTOs;

use Arr;
use App\Syllaby\Publisher\Publications\Contracts\VideoDataContract;

class FacebookVideoData implements VideoDataContract
{
    public function __construct(
        public readonly ?string $caption,
        public readonly ?string $title,
        public ?string $video_id,
    ) {

    }

    public static function fromArray(array $data): self
    {
        return new self(
            caption: Arr::get($data, 'caption'),
            title: Arr::get($data, 'title'),
            video_id: Arr::get($data, 'video_id'),
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function setVideoId(string $videoId): VideoDataContract
    {
        $this->video_id = $videoId;

        return $this;
    }
}