<?php

namespace App\Syllaby\Videos\Vendors\Renders;

use Exception;
use Tests\Support\FakeCreatomate;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Testing\Fakes\Fake;

class Studio extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @throws Exception
     */
    public static function fake(string $driver = 'creatomate')
    {
        return match ($driver) {
            'creatomate' => static::fakeWith(new FakeCreatomate),
            default => throw new Exception('Invalid Studio driver')
        };
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return RenderManager::class;
    }

    /**
     * Swap the implementation with the provided fake.
     */
    private static function fakeWith(Fake $instance)
    {
        return tap($instance, fn ($fake) => static::swap($fake));
    }
}
