<?php

namespace App\Syllaby\Animation\Events;

use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Foundation\Events\Dispatchable;

class AnimationGenerationFailed
{
    use Dispatchable;

    public function __construct(public Asset $asset, public ?Faceless $faceless = null) {}
}
