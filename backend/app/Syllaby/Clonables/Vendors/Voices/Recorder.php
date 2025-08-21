<?php

namespace App\Syllaby\Clonables\Vendors\Voices;

use Illuminate\Support\Facades\Facade;

class Recorder extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return RecorderManager::class;
    }
}
