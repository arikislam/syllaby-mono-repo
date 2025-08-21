<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Play API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration values for interacting with the
    | Google Play Developer API. Set these values in your .env file.
    |
    */

    // API Base URL
    'api_url' => env('GOOGLE_PLAY_API_URL', 'https://androidpublisher.googleapis.com/androidpublisher/v3'),

    // Android Package Name (Application ID)
    'package_name' => env('GOOGLE_PLAY_PACKAGE_NAME'),

    // Google Service Account Credentials (JSON string)
    'credentials_json' => env('GOOGLE_PLAY_CREDENTIALS'),

    // OAuth Configuration
    'oauth' => [
        'scope' => env('GOOGLE_PLAY_OAUTH_SCOPE', 'https://www.googleapis.com/auth/androidpublisher'),
        'audience' => env('GOOGLE_PLAY_OAUTH_AUDIENCE', 'https://oauth2.googleapis.com/token'),
        'token_url' => env('GOOGLE_PLAY_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'token_ttl' => env('GOOGLE_PLAY_OAUTH_TOKEN_TTL', 3600), // 1 hour
    ],
    'active_subscription_plans' => [
        'prod_sblmonth_basic1k',
        'prod_sblmonth_basic500',
        'prod_sblmonth_premium5k',
        'prod_sblmonth_standard15k',
        'prod_sblmonth_standard2k',
        'prod_sblyear_basic6k',
        'prod_sblyear_basic12k',
        'prod_sblyear_standard18k',
    ],

    // Development Environment Settings
    'development' => [
        'use_fake_mode' => env('GOOGLE_PLAY_USE_FAKE_MODE', false),
        'create_as_draft' => env('GOOGLE_PLAY_CREATE_AS_DRAFT', true),
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => env('GOOGLE_PLAY_LOGGING_ENABLED', true),
        'level' => env('GOOGLE_PLAY_LOG_LEVEL', 'debug'),
        'rtdn_channel' => env('GOOGLE_PLAY_RTDN_LOG_CHANNEL', 'google-play-rtdn'), // Dedicated log channel for RTDNs
    ],

    // Cache Configuration
    'cache' => [
        'token_ttl' => env('GOOGLE_PLAY_CACHE_TOKEN_TTL', 3000), // 50 minutes
        'rtdn_prefix' => env('GOOGLE_PLAY_RTDN_CACHE_PREFIX', 'rtdn'), // Cache prefix for RTDN processing
    ],

    // Default values
    'defaults' => [
        'currency' => env('GOOGLE_PLAY_DEFAULT_CURRENCY', 'USD'),
        'min_price_micros' => env('GOOGLE_PLAY_MIN_PRICE_MICROS', 990000), // $0.99
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-Time Developer Notifications (RTDN) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Cloud Pub/Sub used by RTDNs
    |
    */
    'rtdn' => [
        'enabled' => env('GOOGLE_PLAY_RTDN_ENABLED', true),

        // Google Cloud Project Settings will be derived from credentials_json at runtime by GooglePlayServiceProvider

        // Pub/Sub Configuration
        'pubsub' => [
            'topic' => env('GOOGLE_PUBSUB_TOPIC', 'projects/'.env('GOOGLE_CLOUD_PROJECT_ID', 'your-project').'/topics/test-rndt-notification'),
            'subscription' => env('GOOGLE_PUBSUB_SUBSCRIPTION', 'projects/'.env('GOOGLE_CLOUD_PROJECT_ID', 'your-project').'/subscriptions/test_rndt_notification'),
            'push_endpoint' => env('GOOGLE_PUBSUB_PUSH_ENDPOINT', '/google-play/webhook'),
        ],

        // Message Processing Settings
        'processing' => [
            'use_queue' => env('GOOGLE_PLAY_RTDN_USE_QUEUE', true),
            'queue_connection' => env('GOOGLE_PLAY_RTDN_QUEUE_CONNECTION', 'default'),
            'idempotency_ttl' => env('GOOGLE_PLAY_RTDN_IDEMPOTENCY_TTL', 604800), // 7 days
            'max_retries' => env('GOOGLE_PLAY_RTDN_MAX_RETRIES', 3),
        ],

        // Security Settings
        'security' => [
            'validate_jwt' => env('GOOGLE_PLAY_RTDN_VALIDATE_JWT', true),
            'allowed_ips' => env('GOOGLE_PLAY_RTDN_ALLOWED_IPS', ''), // Comma-separated
        ],

        // Notification Types to Process
        'notification_types' => [
            'subscriptions' => env('GOOGLE_PLAY_RTDN_PROCESS_SUBSCRIPTIONS', true),
            'one_time_products' => env('GOOGLE_PLAY_RTDN_PROCESS_ONE_TIME', true),
            'voided_purchases' => env('GOOGLE_PLAY_RTDN_PROCESS_VOIDED', true),
        ],
    ],
];
