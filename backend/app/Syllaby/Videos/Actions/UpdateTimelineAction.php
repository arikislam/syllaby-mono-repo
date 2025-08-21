<?php

namespace App\Syllaby\Videos\Actions;

use App\Syllaby\Videos\Footage;
use App\Syllaby\Metadata\Timeline;

class UpdateTimelineAction
{
    /**
     * Updates footage timeline in storage.
     */
    public function handle(Timeline $timeline, array $source): Timeline
    {
        if ($timeline->rehash($source) === $timeline->hash) {
            return $timeline;
        }

        return tap($timeline)->update(['content' => $source]);
    }
}
