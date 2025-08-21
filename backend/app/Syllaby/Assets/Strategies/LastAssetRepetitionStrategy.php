<?php

namespace App\Syllaby\Assets\Strategies;

use Illuminate\Support\Collection;

class LastAssetRepetitionStrategy implements AssetRepetitionStrategy
{
    /**
     * Repeat the last asset to match the required count.
     */
    public function repeat(Collection $assets, int $required): Collection
    {
        if ($assets->isEmpty() || $required <= $assets->count()) {
            return $assets;
        }

        $result = $assets->toArray();
        $last = $assets->last();

        while (count($result) < $required) {
            $result[] = $last;
        }

        return collect($result);
    }
}
