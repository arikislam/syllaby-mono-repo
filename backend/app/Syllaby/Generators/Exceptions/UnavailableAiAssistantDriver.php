<?php

namespace App\Syllaby\Generators\Exceptions;

use Exception;

class UnavailableAiAssistantDriver extends Exception
{
    public static function fromProvider(string $provider): self
    {
        return new self("Unavailable AI assistant driver: {$provider}");
    }
}
