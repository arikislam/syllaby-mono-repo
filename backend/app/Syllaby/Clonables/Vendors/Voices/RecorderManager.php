<?php

namespace App\Syllaby\Clonables\Vendors\Voices;

use Illuminate\Support\Manager;
use App\Syllaby\Clonables\Contracts\RecorderContract;

class RecorderManager extends Manager
{
    /**
     * Instantiates a Elevenlabs driver.
     */
    public function createElevenlabsDriver(): RecorderContract
    {
        return new Elevenlabs;
    }

    /**
     * Get the default render engine driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'elevenlabs';
    }
}
