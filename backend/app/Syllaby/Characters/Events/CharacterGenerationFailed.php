<?php

namespace App\Syllaby\Characters\Events;

use App\Syllaby\Characters\Character;
use Illuminate\Foundation\Events\Dispatchable;

class CharacterGenerationFailed
{
    use Dispatchable;

    public function __construct(public Character $character) {}
}
