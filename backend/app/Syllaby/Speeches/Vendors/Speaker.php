<?php

namespace App\Syllaby\Speeches\Vendors;

use Illuminate\Support\Facades\Facade;
use App\Syllaby\Speeches\Contracts\SpeakerContract;

/**
 * @method static SpeakerContract driver(string $driver = null)
 *
 * @see SpeakerManager
 */
class Speaker extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SpeakerManager::class;
    }
}
