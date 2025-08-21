<?php

namespace App\Syllaby\Videos\Events;

use App\Syllaby\Videos\Faceless;
use Illuminate\Foundation\Events\Dispatchable;

class FacelessGenerationFailed
{
    use Dispatchable;

    public function __construct(public Faceless $faceless)
    {
    }
}
