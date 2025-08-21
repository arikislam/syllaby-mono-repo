<?php

namespace App\Syllaby\Assets\Strategies;

class AssetRepetitionStrategyFactory
{
    /**
     * Get the appropriate asset repetition strategy.
     */
    public static function make(?string $strategy = null): AssetRepetitionStrategy
    {
        return match ($strategy ?: config('videos.asset_repetition_strategy', 'sequential')) {
            'random' => new RandomRepetitionStrategy,
            'last' => new LastAssetRepetitionStrategy,
            'first' => new FirstAssetRepetitionStrategy,
            default => new SequentialRepetitionStrategy,
        };
    }
}
