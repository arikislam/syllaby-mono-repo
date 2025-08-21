<?php

namespace App\Syllaby\Publisher\Publications\DTOs;

use Arr;
use App\Syllaby\Publisher\Publications\Contracts\VideoDataContract;

readonly class LinkedInVideoData implements VideoDataContract
{
    public function __construct(
        public ?string $caption,
        public ?string $title,
        public ?string $visibility,
    ) {
    }


    public static function fromArray(array $data): self
    {
        return new self(
            caption: Arr::get($data, 'caption'),
            title: Arr::get($data, 'title'),
            visibility: Arr::get($data, 'visibility', 'PUBLIC'),
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}