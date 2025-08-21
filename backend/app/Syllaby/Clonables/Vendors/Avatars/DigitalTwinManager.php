<?php

namespace App\Syllaby\Clonables\Vendors\Avatars;

use Illuminate\Support\Manager;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Syllaby\Clonables\Contracts\UserCloneContract;

class DigitalTwinManager extends Manager
{
    /**
     * Instantiates a FastVideo driver.
     */
    public function createFastvideoDriver(): UserCloneContract
    {
        return new FastVideo;
    }

    /**
     * DigitalTwin default driver
     */
    public function getDefaultDriver(): string
    {
        return RealCloneProvider::FASTVIDEO->value;
    }
}
