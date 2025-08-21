<?php

namespace App\Syllaby\Videos\Observers;

use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Loggers\DTOs\VideoLogData;
use App\Syllaby\Loggers\Services\VideoLogger;

class VideoObserver
{
    /**
     * Handle the Video "updated" event.
     */
    public function updated(Video $video): void
    {
        match (true) {
            $this->renderingStarted($video) => VideoLogger::record($this->loggable($video))->start(),
            $this->renderingEnded($video) => VideoLogger::record($this->loggable($video))->end(),
            default => null
        };
    }

    /**
     * Checks whether the current video start its rendering process.
     */
    private function renderingStarted(Video $video): bool
    {
        if (!$video->isDirty('status')) {
            return false;
        }

        return $video->status === VideoStatus::RENDERING;
    }

    /**
     * Checks whether the current video generation process ended.
     */
    private function renderingEnded(Video $video): bool
    {
        if (!$video->isDirty('status')) {
            return false;
        }

        return in_array($video->status, [
            VideoStatus::FAILED,
            VideoStatus::COMPLETED,
        ]);
    }

    /**
     * Converts into a video loggable object.
     */
    private function loggable(Video $video): VideoLogData
    {
        return VideoLogData::fromVideo($video);
    }
}
