<?php

namespace App\Syllaby\Assets\Strategies;

use Illuminate\Support\Collection;

class FirstAssetRepetitionStrategy implements AssetRepetitionStrategy
{
    /**
     * Repeat the first asset to match the required count.
     */
    public function repeat(Collection $assets, int $required): Collection
    {
        if ($assets->isEmpty() || $required <= $assets->count()) {
            return $assets;
        }

        $result = $assets->toArray();
        $first = $assets->first();

        while (count($result) < $required) {
            $result[] = $first;
        }

        return collect($result);
    }
}
