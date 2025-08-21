<?php

namespace App\Syllaby\Videos\Actions;

use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Jobs\DeleteVideo;

class DeleteVideoAction
{
    /**
     * Deletes the given video and dependencies from storage.
     */
    public function handle(Video $video, bool $deleteUnusedAssets = false, bool $sync = false): void
    {
        $sync ? dispatch_sync(new DeleteVideo($video, $deleteUnusedAssets)) : dispatch(new DeleteVideo($video, $deleteUnusedAssets));
    }
}
