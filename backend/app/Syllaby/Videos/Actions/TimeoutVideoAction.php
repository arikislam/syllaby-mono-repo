<?php

namespace App\Syllaby\Videos\Actions;

use App\Syllaby\Videos\Video;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Videos\Enums\VideoStatus;

class TimeoutVideoAction
{
    /**
     * Marks a video as failed when it's stuck in any status other than
     * completed or failed for more than 24 hours.
     */
    public function handle(Video $video): bool
    {
        if ($video->isFinished()) {
            return false;
        }

        if ($video->updated_at->diffInHours(now()) < 24) {
            return false;
        }

        Log::error('Generation timeout for video {id}', ['id' => $video->id]);

        return $video->update([
            'status' => VideoStatus::FAILED,
        ]);
    }
}
