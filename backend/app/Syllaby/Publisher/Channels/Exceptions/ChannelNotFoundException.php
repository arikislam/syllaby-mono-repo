<?php

namespace App\Syllaby\Publisher\Channels\Exceptions;

use RuntimeException;

class ChannelNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'We are unable to find channel associated with this account. Please try again. If the problem persists, contact support.'
    ) {
        parent::__construct($message);
    }
}