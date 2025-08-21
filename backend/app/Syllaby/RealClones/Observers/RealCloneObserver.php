<?php

namespace App\Syllaby\RealClones\Observers;

use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Loggers\DTOs\VideoLogData;
use App\Syllaby\Loggers\Services\VideoLogger;
use App\Syllaby\RealClones\Enums\RealCloneStatus;

class RealCloneObserver
{
    /**
     * Handle the Real Clone "updated" event.
     */
    public function updated(RealClone $clone): void
    {
        match (true) {
            $this->generationStarted($clone) => VideoLogger::record($this->loggable($clone))->start(),
            $this->generationEnded($clone) => VideoLogger::record($this->loggable($clone))->end(),
            default => null
        };
    }

    /**
     * Checks whether the current real clone start its generation process.
     */
    private function generationStarted(RealClone $clone): bool
    {
        if (!$clone->isDirty('status')) {
            return false;
        }

        return $clone->status === RealCloneStatus::GENERATING;
    }

    /**
     * Checks whether the current real clone generation process ended.
     */
    private function generationEnded(RealClone $clone): bool
    {
        if (!$clone->isDirty('status')) {
            return false;
        }

        return in_array($clone->status, [
            RealCloneStatus::FAILED,
            RealCloneStatus::COMPLETED,
        ]);
    }

    /**
     * Converts the real clone into a video loggable object.
     */
    private function loggable(RealClone $clone): VideoLogData
    {
        return VideoLogData::fromRealClone($clone);
    }
}
