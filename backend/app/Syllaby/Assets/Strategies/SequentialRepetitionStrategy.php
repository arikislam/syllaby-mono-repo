<?php

namespace App\Syllaby\Assets\Strategies;

use Illuminate\Support\Collection;

class SequentialRepetitionStrategy implements AssetRepetitionStrategy
{
    /**
     * Repeat assets sequentially to match the required count.
     */
    public function repeat(Collection $assets, int $required): Collection
    {
        if ($assets->isEmpty() || $required <= $assets->count()) {
            return $assets;
        }

        $result = collect();
        $original = $assets->count();

        for ($i = 0; $i < $required; $i++) {
            $asset = $assets[$i % $original];
            $result->push($asset);
        }

        return $result;
    }
}
