<?php

namespace App\Syllaby\Clonables\Vendors\Avatars;

use App\Syllaby\Clonables\Enums\CloneStatus;

readonly class UserCloneData
{
    public function __construct(
        public string $provider,
        public string $provider_id,
        public CloneStatus $status,
    ) {
    }
}
