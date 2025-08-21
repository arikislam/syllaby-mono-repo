<?php

namespace App\Syllaby\RealClones\Vendors;

use Illuminate\Support\Facades\Facade;

/**
 * @method static PresenterManager driver(string $driver = null)
 *
 * @see PresenterManager
 */
class Presenter extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return PresenterManager::class;
    }
}
