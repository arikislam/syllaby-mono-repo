<?php

namespace App\Syllaby\Clonables\Contracts;

use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Clonables\Vendors\Avatars\UserCloneData;

interface UserCloneContract
{
    public function clone(Clonable $clonable, string $source): UserCloneData;
}
