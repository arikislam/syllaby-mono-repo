<?php

namespace App\Syllaby\Loggers\DTOs;

use Carbon\Carbon;
use App\Syllaby\Videos\Video;
use App\Syllaby\RealClones\RealClone;

class VideoLogData
{
    public function __construct(
        public string $id,
        public string $status,
        public string $provider,
        public ?Carbon $synced_at,
    )
    {
    }

    public static function fromRealClone(RealClone $clone): self
    {
        return new static(
            id: $clone->id,
            status: $clone->status->value,
            provider: $clone->provider,
            synced_at: $clone->synced_at,
        );
    }

    public static function fromVideo(Video $video): self
    {
        return new static(
            id: $video->id,
            status: $video->status->value,
            provider: $video->provider,
            synced_at: $video->synced_at,
        );
    }
}
