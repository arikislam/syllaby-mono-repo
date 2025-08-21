<?php

namespace App\Syllaby\Videos\Contracts;

use App\Syllaby\Videos\DTOs\ModeratorData;

interface ImageModerator
{
    /**
     * See if a Media is Safe for Work
     */
    public function inspect(string $url): ModeratorData;
}
