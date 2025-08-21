<?php

namespace App\Syllaby\Generators\DTOs;

readonly class ChatResponse
{
    /**
     * Create a DTO instance.
     */
    public function __construct(
        public ?string $text,
        public ?int $completionTokens = 0,
    ) {}
}
