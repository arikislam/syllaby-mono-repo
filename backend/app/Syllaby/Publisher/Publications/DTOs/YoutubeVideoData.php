<?php

namespace App\Syllaby\Publisher\Publications\DTOs;

use Str;
use Illuminate\Support\Arr;
use App\Syllaby\Publisher\Publications\Contracts\VideoDataContract;

readonly class YoutubeVideoData implements VideoDataContract
{
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?string $privacy_status,
        public ?int $category,
        public ?array $tags,
        public ?string $license,
        public ?bool $embeddable,
        public ?bool $made_for_kids,
        public ?bool $notify_subscribers = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: Str::limitNaturally(Arr::get($data, 'title'), 2200),
            description: Arr::get($data, 'description'),
            privacy_status: Arr::get($data, 'privacy_status'),
            category: Arr::get($data, 'category'),
            tags: Arr::get($data, 'tags'),
            license: Arr::get($data, 'license'),
            embeddable: Arr::get($data, 'embeddable'),
            made_for_kids: Arr::get($data, 'made_for_kids'),
            notify_subscribers: Arr::get($data, 'notify_subscribers'),
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}
