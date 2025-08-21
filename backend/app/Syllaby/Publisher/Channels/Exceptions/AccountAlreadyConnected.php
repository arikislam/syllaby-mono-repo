<?php

namespace App\Syllaby\Publisher\Channels\Exceptions;

use Exception;

class AccountAlreadyConnected extends Exception
{
    public function __construct(string $message = 'This account is already connected to another user.')
    {
        parent::__construct($message);
    }
}