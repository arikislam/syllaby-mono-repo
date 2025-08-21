<?php

namespace App\Syllaby\Generators\Vendors\Transcribers;

use Illuminate\Support\Facades\Facade;

/**
 * @method run(string $url, array $options = []): ?CaptionResponse
 */
class Transcriber extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return TranscriberManager::class;
    }
}
