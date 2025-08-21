<?php

namespace App\Syllaby\Clonables\Contracts;

use App\Syllaby\Speeches\Voice;
use App\Syllaby\Clonables\Clonable;

interface RecorderContract
{
    public function clone(Voice $voice, Clonable $clonable): ?Voice;

    public function remove(Voice $voice): bool;
}
