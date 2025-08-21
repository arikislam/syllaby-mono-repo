<?php

return [
    'campaigns' => env('SYLLABY_CAMPAIGNS'),

    'users' => [
        /*
        |--------------------------------------------------------------------------
        | Default User Settings
        |--------------------------------------------------------------------------
        |
        | User account default settings that will be set upon registration.
        |
        */
        'settings' => [
            'mailing_list' => false,
            'completed_experience_survey' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Default Notifications Settings
        |--------------------------------------------------------------------------
        |
        | This option controls the default notifications settings that will be set on
        | every user upon registration. By default, all channels will be on, but
        | users may turn off any of them later in their profile settings page.
        |
        */
        'notifications' => [
            'videos' => ['mail' => true, 'database' => true],
            'real_clones' => ['mail' => true, 'database' => true],
            'publications' => ['mail' => true, 'database' => true],
            'scheduler' => [
                'reminders' => ['mail' => true, 'database' => true],
                'generated' => ['mail' => true, 'database' => true],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Syllaby Plans
    |--------------------------------------------------------------------------
    |
    | Below you may define all the active plans supported by the application.
    |
    | In addition to defining plans, you may also define its features and limitations,
    | including a short description of it.
    |
    */
    'plans' => [
        'basic' => [
            'name' => 'Basic',
            'product_id' => env('STRIPE_BASIC_PLAN'),
            'short_description' => 'Basic Plan Description',
            'monthly_prices' => [
                env('STRIPE_BASIC_MONTHLY_25_PRICE'),
                env('STRIPE_BASIC_MONTHLY_49_PRICE'),
            ],
            'yearly_prices' => [
                env('STRIPE_BASIC_YEARLY_255_PRICE'),
                env('STRIPE_BASIC_YEARLY_499_PRICE'),
            ],
            'trial_days' => config('services.stripe.trial_days'),
            'features' => [
                'video' => true,
                'consistency_tracker' => true,
                'calendar' => true,
                'article_writer' => true,
                'tiktok' => true,
                'youtube' => true,
                'facebook' => true,
                'instagram' => true,
                'linkedin' => true,
                'threads' => true,
                'thumbnails' => true,
                'max_voice_clones' => '1',
                'max_scheduled_posts' => '20',
                'max_scheduled_weeks' => '2',
                'storage' => '5369709120', // 5GB
            ],
        ],

        'standard' => [
            'name' => 'Standard',
            'product_id' => env('STRIPE_STANDARD_PLAN'),
            'short_description' => 'Standard Plan Description',
            'monthly_prices' => [
                env('STRIPE_STANDARD_MONTHLY_74_PRICE'),
                env('STRIPE_STANDARD_MONTHLY_98_PRICE'),
            ],
            'yearly_prices' => [
                env('STRIPE_STANDARD_YEARLY_754_PRICE'),
                env('STRIPE_STANDARD_YEARLY_999_PRICE'),
            ],
            'trial_days' => config('services.stripe.trial_days'),
            'features' => [
                'video' => true,
                'consistency_tracker' => true,
                'calendar' => true,
                'article_writer' => true,
                'tiktok' => true,
                'youtube' => true,
                'facebook' => true,
                'instagram' => true,
                'linkedin' => true,
                'threads' => true,
                'thumbnails' => true,
                'max_voice_clones' => '3',
                'max_scheduled_posts' => '60',
                'max_scheduled_weeks' => '4',
                'storage' => '10737418240', // 10GB
            ],
        ],

        'premium' => [
            'name' => 'Premium',
            'product_id' => env('STRIPE_PREMIUM_PLAN'),
            'short_description' => 'Premium Plan Description',
            'monthly_prices' => [
                env('STRIPE_PREMIUM_MONTHLY_149_PRICE'),
            ],
            'yearly_prices' => [
                env('STRIPE_PREMIUM_YEARLY_1520_PRICE'),
            ],
            'trial_days' => config('services.stripe.trial_days'),
            'features' => [
                'video' => true,
                'consistency_tracker' => true,
                'calendar' => true,
                'article_writer' => true,
                'tiktok' => true,
                'youtube' => true,
                'facebook' => true,
                'instagram' => true,
                'linkedin' => true,
                'threads' => true,
                'thumbnails' => true,
                'max_voice_clones' => '5',
                'max_scheduled_posts' => '*',
                'max_scheduled_weeks' => '*',
                'storage' => '21474836480',  // 20GB
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'product_id' => env('STRIPE_ENTERPRISE_PLAN'),
            'short_description' => 'Enterprise Plan Description',
            'monthly_prices' => [
                env('STRIPE_ENTERPRISE_MONTHLY_542_PRICE'),
            ],
            'yearly_prices' => [],
            'trial_days' => config('services.stripe.trial_days'),
            'features' => [
                'video' => true,
                'consistency_tracker' => true,
                'calendar' => true,
                'article_writer' => true,
                'tiktok' => true,
                'youtube' => true,
                'facebook' => true,
                'instagram' => true,
                'linkedin' => true,
                'threads' => true,
                'max_voice_clones' => '30',
                'max_scheduled_posts' => '*',
                'max_scheduled_weeks' => '*',
                'storage' => '42949672960', // 40GB
            ],
        ],

        'retention-lite' => [
            'name' => 'Retention Lite',
            'product_id' => env('STRIPE_RETENTION_LITE_PLAN'),
            'short_description' => 'Retention Lite Plan Description',
            'monthly_prices' => [
                env('STRIPE_RETENTION_LITE_PRICE'),
            ],
            'trial_days' => 0,
            'features' => [
                'video' => true,
                'consistency_tracker' => true,
                'calendar' => true,
                'article_writer' => true,
                'tiktok' => true,
                'youtube' => true,
                'facebook' => true,
                'instagram' => true,
                'linkedin' => true,
                'threads' => true,
                'thumbnails' => true,
                'max_voice_clones' => '0',
                'max_scheduled_posts' => '5',
                'max_scheduled_weeks' => '1',
                'storage' => '2147483648',  // 2GB
            ],
        ],

        'retention-pause' => [
            'name' => 'Retention Pause',
            'product_id' => env('STRIPE_RETENTION_PAUSE_PLAN'),
            'short_description' => 'Retention Pause Plan Description',
            'monthly_prices' => [
                env('STRIPE_RETENTION_PAUSE_PRICE'),
            ],
            'trial_days' => 0,
            'features' => [
                'video' => false,
                'consistency_tracker' => false,
                'calendar' => false,
                'article_writer' => false,
                'tiktok' => false,
                'youtube' => false,
                'facebook' => false,
                'instagram' => false,
                'linkedin' => false,
                'threads' => false,
                'thumbnails' => false,
                'max_voice_clones' => '0',
                'max_scheduled_posts' => '0',
                'max_scheduled_weeks' => '0',
                'storage' => '0',
            ],
        ],
    ],
];
