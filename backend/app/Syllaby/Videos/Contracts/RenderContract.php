<?php

namespace App\Syllaby\Videos\Contracts;

use App\Syllaby\Videos\Video;

interface RenderContract
{
    /**
     * Triggers the render process.
     */
    public function render(Video $video): array;

    /**
     * Calculate and charge the user credits.
     */
    public function charge(Video $video, int $duration): void;

    /**
     * Ping the given render to fetch latest changes.
     */
    public function ping(Video $video): Video;
}
