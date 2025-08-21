<?php

namespace App\Syllaby\Publisher\Publications\DTOs;

use Arr;
use App\Syllaby\Publisher\Publications\Contracts\VideoDataContract;

readonly class ThreadsVideoData implements VideoDataContract
{
    public function __construct(
        public ?string $caption,
    ) {}

    public static function fromArray(array $data): VideoDataContract
    {
        return new self(
            caption: Arr::get($data, 'caption'),
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}
