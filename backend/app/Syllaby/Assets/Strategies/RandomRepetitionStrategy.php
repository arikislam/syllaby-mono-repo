<?php

namespace App\Syllaby\Assets\Strategies;

use Illuminate\Support\Collection;

class RandomRepetitionStrategy implements AssetRepetitionStrategy
{
    /**
     * Repeat assets randomly to match the required count.
     */
    public function repeat(Collection $assets, int $required): Collection
    {
        if ($assets->isEmpty() || $required <= $assets->count()) {
            return $assets;
        }

        $result = $assets->toArray();
        $original = count($result);

        while (count($result) < $required) {
            $randomIndex = rand(0, $original - 1);
            $result[] = $assets[$randomIndex];
        }

        return collect($result);
    }
}
