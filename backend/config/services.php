<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_SES_ACCESS_KEY_ID'),
        'secret' => env('AWS_SES_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'options' => [
            'ConfigurationSetName' => 'ses-watcher',
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'currency' => env('CASHIER_CURRENCY'),
        'model' => App\Syllaby\Users\User::class,
        'trial_days' => env('SUBSCRIPTION_TRIAL_DAYS', 7),
        'products' => [
            env('STRIPE_BASIC_PLAN'),
            env('STRIPE_STANDARD_PLAN'),
            env('STRIPE_PREMIUM_PLAN'),
        ],
        'google_play_products' => [
            env('GOOGLE_PLAY_BASIC_PLAN'),
            env('GOOGLE_PLAY_STANDARD_PLAN'),
            env('GOOGLE_PLAY_PREMIUM_PLAN'),
        ],
        'add_ons' => [
            'storage' => [
                'product' => env('STRIPE_STORAGE_PLAN'),
                'monthly' => env('STRIPE_MONTHLY_STORAGE_PRICE'),
                'yearly' => env('STRIPE_YEARLY_STORAGE_PRICE'),
            ],
        ],
        'retention' => [
            env('STRIPE_RETENTION_PAUSE_PLAN'),
            env('STRIPE_RETENTION_LITE_PLAN'),
        ],
        'unsub_coupon' => env('STRIPE_UNSUB_COUPON', 'UNSUB50'),
        'discounts' => [],
    ],

    'heygen' => [
        'key' => env('HEYGEN_KEY'),
        'url' => env('HEYGEN_URL', 'https://api.heygen.com/v1'),
        'webhook' => [
            'secret' => env('HEYGEN_WEBHOOK_SECRET', null),
        ],
    ],

    'd-id' => [
        'key' => env('DID_KEY'),
        'url' => env('DID_URL', 'https://api.d-id.com'),
        'webhook_url' => env('DID_WEBHOOK_URL'),
    ],

    'fastvideo' => [
        'app_id' => env('FASTVIDEO_APP_ID'),
        'rapid_api_key' => env('FASTVIDEO_RAPID_API_KEY'),
        'url' => env('FASTVIDEO_URL', 'https://fastvideo.p.rapidapi.com'),
    ],

    'replicate' => [
        'key' => env('REPLICATE_KEY'),
        'url' => env('REPLICATE_URL', 'https://api.replicate.com/v1'),
        'webhook' => [
            'url' => env('REPLICATE_WEBHOOK_URL'),
            'secret' => env('REPLICATE_WEBHOOK_SECRET'),
            'filters' => env('REPLICATE_WEBHOOK_FILTERS', ['completed']),
        ],
    ],

    'character_consistency' => [
        'url' => env('CHARACTER_CONSISTENCY_URL', 'https://cc.syllaby.dev/api'),
        'webhook' => [
            'url' => env('CHARACTER_CONSISTENCY_WEBHOOK_URL'),
        ],
    ],

    'creatomate' => [
        'key' => env('CREATOMATE_KEY'),
        'url' => env('CREATOMATE_URL', 'https://api.creatomate.com/v1'),
        'webhook_url' => env('CREATOMATE_WEBHOOK_URL'),
    ],

    'elevenlabs' => [
        'key' => env('ELEVENLABS_KEY'),
        'url' => env('ELEVENLABS_URL', 'https://api.elevenlabs.io/v1'),
    ],

    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect' => env('YOUTUBE_REDIRECT_URI'),
        'developer_key' => env('YOUTUBE_DEVELOPER_KEY'),
        'scopes' => [
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube.readonly', // for getting channel info
        ],
        'params' => [
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'https://react.syllaby.dev/login/google'),
        'scopes' => ['openid', 'profile', 'email'],
        'params' => ['prompt' => 'consent select_account'],
    ],

    'tiktok' => [
        'client_id' => env('TIKTOK_CLIENT_ID'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect' => env('TIKTOK_REDIRECT_URI', 'https://ai.syllaby.io/social/callback/tiktok/'),
        'base_url' => env('TIKTOK_URL', 'https://open.tiktokapis.com/v2'),
        'scopes' => [
            'user.info.basic',
            'video.list',
            'video.upload',
            'video.publish',
        ],
        'webhook_threshold' => env('TIKTOK_WEBHOOK_THRESHOLD', 30),

        'pixel' => [
            'id' => env('TIKTOK_PIXEL_ID'),
            'url' => env('TIKTOK_PIXEL_URL'),
            'access_token' => env('TIKTOK_PIXEL_TOKEN'),
            'test_code' => env('TIKTOK_PIXEL_TEST_CODE'),
        ],
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', 'https://ai.syllaby.io/social-connect/linkedin'),
        'protocol_version' => env('LINKEDIN_PROTOCOL_VERSION', '2.0.0'),
        'api_version' => env('LINKEDIN_API_VERSION', '202409'),
        'scopes' => [
            'r_organization_followers',
            'r_organization_social',
            'rw_organization_admin',
            'r_organization_social_feed',
            'w_member_social',
            'w_organization_social',
            'r_basicprofile',
            'w_organization_social_feed',
            'w_member_social_feed',
        ],
        'base_url' => env('LINKEDIN_URL', 'https://api.linkedin.com/v2'),
    ],

    'facebook' => [
        'pixel' => [
            'id' => env('FACEBOOK_PIXEL_ID'),
            'access_token' => env('FACEBOOK_PIXEL_TOKEN'),
            'test_code' => env('FACEBOOK_PIXEL_TEST_CODE'),
        ],
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
        'base_url' => 'https://graph.facebook.com',
        'graph_version' => 'v19.0',
        'webhook_secret' => env('FACEBOOK_WEBHOOK_SECRET'),
        'scopes' => [
            'email',
            'pages_show_list',
            'pages_manage_posts',
            'read_insights',
            'business_management',
        ],
        'insta_scopes' => [
            'instagram_basic',
            'instagram_content_publish',
        ],
    ],

    'threads' => [
        'client_id' => env('THREADS_CLIENT_ID'),
        'client_secret' => env('THREADS_CLIENT_SECRET'),
        'redirect' => env('THREADS_REDIRECT_URI'),
        'scopes' => [
            'threads_basic',
            'threads_content_publish',
        ],
    ],

    'pexels' => [
        'url' => env('PEXELS_API_URL'),
        'key' => env('PEXELS_API_KEY'),
    ],

    'mailerlite' => [
        'url' => env('MAILERLITE_URL'),
        'token' => env('MAILERLITE_TOKEN'),
        'groups' => [
            'non-user' => '112687732079199972',
            'trial' => '112687698988237932',
            'trial-canceled' => '112687706308347449',
            'customer' => '112687688286472005',
            'canceled' => '112687724981389086',
        ],
    ],

    'keywordtool' => [
        'key' => env('KEYWORDTOOL_KEY'),
        'url' => env('KEYWORDTOOL_URL', 'https://api.keywordtool.io/v2-sandbox'),
    ],

    'welcome_email' => [
        'template_id' => env('WELCOME_VIDEO_TEMPLATE_ID', 'f3c1d095fd474cad99b9be9f064467a3'),
    ],

    'slack_alerts' => [
        'subscriptions' => env('SUBSCRIPTION_ALERT_WEBHOOK_URL'),
        'real_clone_request' => env('REAL_CLONE_REQUEST_WEBHOOK_URL'),
    ],

    'facegen' => [
        'url' => env('FACEGEN_URL', 'https://facegen.syllaby.io'),
    ],

    'trial_limit' => [
        'faceless' => env('TRIAL_LIMIT_FACELESS', 5),
        'custom' => env('TRIAL_LIMIT_CUSTOM', 5),
        'edited-faceless' => env('TRIAL_LIMIT_EDITED_FACELESS', 5),
    ],

    'claude' => [
        'key' => env('CLAUDE_KEY'),
        'max_tokens' => env('CLAUDE_MAX_TOKENS', 8192),
        'url' => env('CLAUDE_URL', 'https://api.anthropic.com/v1'),
        'model' => env('CLAUDE_AI_MODEL', 'claude-3-5-sonnet-20240620'),
        'temperature' => env('CLAUDE_AI_TEMPERATURE', 1.0),
    ],

    'xai' => [
        'key' => env('XAI_API_KEY'),
        'url' => env('XAI_URL', 'https://api.x.ai/v1'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    'assistant' => [
        'default' => env('ASSISTANT_DRIVER', 'gpt'),
        'drivers' => ['gpt', 'claude', 'gemini'],
    ],

    'minimax' => [
        'api_key' => env('MINIMAX_API_KEY'),
        'base_url' => 'https://api.minimaxi.chat/v1',
        'rate_limit_attempts' => 100,
    ],

    'firecrawl' => [
        'key' => env('FIRECRAWL_KEY'),
        'rate_limit_attempts' => 20,
    ],

    'posthog' => [
        'api_key' => env('POSTHOG_API_KEY'),
        'url' => 'https://us.i.posthog.com',
    ],

    'animation' => [
        'use_polling' => env('USE_ANIMATION_POLLING', false),
    ],

    'remotion' => [
        'access_key_id' => env('REMOTION_AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('REMOTION_AWS_SECRET_ACCESS_KEY'),
        'region' => env('REMOTION_APP_REGION'),
        'function_name' => env('REMOTION_APP_FUNCTION_NAME'),
        'serve_url' => env('REMOTION_APP_SERVE_URL'),
        'webhook_url' => env('REMOTION_WEBHOOK_URL'),
    ],

    'redfin' => [
        'base_url' => 'https://www.redfin.com/stingray/api/home',
    ],

    'jvzoo' => [
        'webhook_secret' => env('JVZOO_WEBHOOK_SECRET'),
        'trial_days' => env('JVZOO_TRIAL_DAYS', 7),
        'plans' => [
            // JVZoo to Stripe Plan Mappings (for backward compatibility)
            env('JVZOO_BASIC_MONTHLY_25_PRICE') => env('STRIPE_BASIC_MONTHLY_25_PRICE'),
            env('JVZOO_BASIC_MONTHLY_49_PRICE') => env('STRIPE_BASIC_MONTHLY_49_PRICE'),
            env('JVZOO_BASIC_YEARLY_255_PRICE') => env('STRIPE_BASIC_YEARLY_255_PRICE'),
            env('JVZOO_BASIC_YEARLY_499_PRICE') => env('STRIPE_BASIC_YEARLY_499_PRICE'),

            // JVZoo Standard Plan Mappings
            env('JVZOO_STANDARD_MONTHLY_74_PRICE') => env('STRIPE_STANDARD_MONTHLY_74_PRICE'),
            env('JVZOO_STANDARD_MONTHLY_98_PRICE') => env('STRIPE_STANDARD_MONTHLY_98_PRICE'),
            env('JVZOO_STANDARD_YEARLY_754_PRICE') => env('STRIPE_STANDARD_YEARLY_754_PRICE'),
            env('JVZOO_STANDARD_YEARLY_999_PRICE') => env('STRIPE_STANDARD_YEARLY_999_PRICE'),

            // JVZoo Premium Plan Mappings
            env('JVZOO_PREMIUM_MONTHLY_149_PRICE') => env('STRIPE_PREMIUM_MONTHLY_149_PRICE'),
            env('JVZOO_PREMIUM_YEARLY_1520_PRICE') => env('STRIPE_PREMIUM_YEARLY_1520_PRICE'),

        ],
    ],
];
