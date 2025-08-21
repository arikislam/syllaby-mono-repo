<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Business;

use InvalidArgumentException;

class Factory
{
    public function for(string $provider)
    {
        return match ($provider) {
            'linkedin' => app(LinkedInProvider::class),
            default => throw new InvalidArgumentException("Provider {$provider} is not supported."),
        };
    }
}
