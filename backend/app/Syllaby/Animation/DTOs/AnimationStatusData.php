<?php

namespace App\Syllaby\Animation\DTOs;

use Illuminate\Support\Arr;

final readonly class AnimationStatusData
{
    public function __construct(
        public string $status, // This status is different from the MinimaxStatus enum. It's just a string in API response.
        public ?string $fileId = null,
    ) {}

    public static function fromResponse(array $response): self
    {
        return new self(
            status: Arr::get($response, 'status'),
            fileId: Arr::get($response, 'file_id'),
        );
    }
}
