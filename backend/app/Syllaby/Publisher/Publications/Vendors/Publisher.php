<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use Illuminate\Support\Facades\Facade;

/**
 * @method driver(string $driver = null)
 *
 * @see PublishManager
 */
class Publisher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PublishManager::class;
    }
}
