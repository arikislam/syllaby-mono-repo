<?php

namespace App\Syllaby\Generators\Vendors\Images;

use InvalidArgumentException;

class Factory
{
    /**
     * Resolve the image generator for the given provider.
     */
    public function for(string $provider): mixed
    {
        return match ($provider) {
            'syllaby' => app(Syllaby::class),
            'replicate' => app(Replicate::class),
            default => throw new InvalidArgumentException('Invalid provider'),
        };
    }
}
