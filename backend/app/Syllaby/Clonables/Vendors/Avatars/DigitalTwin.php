<?php

namespace App\Syllaby\Clonables\Vendors\Avatars;

use Illuminate\Support\Facades\Facade;

class DigitalTwin extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return DigitalTwinManager::class;
    }
}
