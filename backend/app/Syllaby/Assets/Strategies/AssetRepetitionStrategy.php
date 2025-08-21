<?php

namespace App\Syllaby\Assets\Strategies;

use Illuminate\Support\Collection;

interface AssetRepetitionStrategy
{
    /**
     * Repeat assets to match the required count.
     *
     * @param  Collection  $assets  The original assets collection
     * @param  int  $required  The number of assets needed
     * @return Collection The expanded collection of assets
     */
    public function repeat(Collection $assets, int $required): Collection;
}
