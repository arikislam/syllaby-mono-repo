<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asset Repetition Strategy
    |--------------------------------------------------------------------------
    |
    | This option controls how assets are repeated when there aren't enough
    | to match the number of audio chunks in a URL-based video.
    |
    | Supported: "sequential", "random", "last", "first"
    |
    */
    'asset_repetition_strategy' => env('ASSET_REPETITION_STRATEGY', 'sequential'),
];
