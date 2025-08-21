<?php

namespace App\Syllaby\Publisher\Publications\DTOs;

use Arr;
use App\Syllaby\Publisher\Publications\Contracts\VideoDataContract;

class TikTokVideoData implements VideoDataContract
{
    public function __construct(
        public readonly ?string $caption,
        public readonly ?bool $allow_comments,
        public readonly ?bool $allow_duet,
        public readonly ?bool $allow_stitch,
        public readonly ?string $privacy_status,
        public ?string $publish_id,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            caption: Arr::get($data, 'caption'),
            allow_comments: Arr::get($data, 'allow_comments'),
            allow_duet: Arr::get($data, 'allow_duet'),
            allow_stitch: Arr::get($data, 'allow_stitch'),
            privacy_status: Arr::get($data, 'privacy_status'),
            publish_id: Arr::get($data, 'publish_id'),
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function setPublishId(string $publishId): self
    {
        $this->publish_id = $publishId;

        return $this;
    }
}