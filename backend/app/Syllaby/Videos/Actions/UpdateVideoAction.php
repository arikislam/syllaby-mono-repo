<?php

namespace App\Syllaby\Videos\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;

class UpdateVideoAction
{
    /**
     * Update video details.
     */
    public function handle(Video $video, array $input): Video
    {
        return tap($video)->update([
            'title' => Arr::get($input, 'title', $video->title),
        ]);
    }
}
