<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Daily Posting Limits
    |--------------------------------------------------------------------------
    |
    | This configuration defines the maximum number of posts allowed in a 24-hour period
    | for each social media platform. These limits are based on the platform guidelines
    | as documented at https://support.buffer.com/article/646-daily-posting-limits
    |
    */
    'limits' => [
        'facebook' => 35,
        'instagram' => 50,
        'linkedin' => 50,
        'tiktok' => 15,
        'youtube' => 10,
        'threads' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Format
    |--------------------------------------------------------------------------
    |
    | This defines the format for cache keys used to track publication limits.

    | Available placeholders:
    | - :user - User
    | - :channel - Channel
    | - :platform - Platform name (facebook, instagram, etc.)
    | - :date - Target date in Y-m-d format
    |
    */
    'cache_format' => 'publications:{user}:{channel}:{platform}:{date}',
];
