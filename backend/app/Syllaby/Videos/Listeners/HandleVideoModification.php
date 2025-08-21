<?php

namespace App\Syllaby\Videos\Listeners;

use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Events\VideoModified;

class HandleVideoModification
{
    public function handle(VideoModified $event): bool
    {
        $video = $event->video;

        if ($video->faceless->hasPendingModifications()) {
            return $video->update(['status' => VideoStatus::MODIFYING]);
        }

        return $video->update(['status' => VideoStatus::MODIFIED]);
    }
}
