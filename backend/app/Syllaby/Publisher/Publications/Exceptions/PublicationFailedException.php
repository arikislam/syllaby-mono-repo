<?php

namespace App\Syllaby\Publisher\Publications\Exceptions;

use Exception;

class PublicationFailedException extends Exception
{
    public static function permissionRequired(): self
    {
        return new self(__('publish.lost_permission'));
    }

    public static function malformedMedia(): self
    {
        return new self(__('publish.malformed_media'));
    }
}
