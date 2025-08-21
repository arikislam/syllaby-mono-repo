<?php

namespace App\Syllaby\Videos\Events;

use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Foundation\Events\Dispatchable;

class VideoModified
{
    use Dispatchable;

    public function __construct(public Video $video) {}
}
