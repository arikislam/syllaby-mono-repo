<?php

namespace Tests\Support;

use App\Syllaby\Videos\DTOs\ModeratorData;
use App\Syllaby\Videos\Contracts\ImageModerator;

class FakeModerator implements ImageModerator
{
    public function inspect(string $url): ModeratorData
    {
        return new ModeratorData(flagged: false);
    }
}
