<?php

namespace App\Syllaby\Publisher\Channels\DTOs;

class MetaPageData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $token,
        public readonly array $roles = [],
        public readonly ?string $avatar = null
    ) {
    }
}