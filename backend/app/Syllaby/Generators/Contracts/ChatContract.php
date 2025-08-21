<?php

namespace App\Syllaby\Generators\Contracts;

use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\DTOs\ChatResponse;

interface ChatContract
{
    public function send(string $message, ?ChatConfig $config): ChatResponse;
}
