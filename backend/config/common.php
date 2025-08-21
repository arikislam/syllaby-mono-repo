<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Date & Time Format
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default date and time format for your application.
    | M (month), d (day), Y (year), g (hour), i (minute), A (am/pm)
    | M d, Y g:i A => Dec 31, 2021 11:59 PM
    |
    */
    'date_time_format' => 'M d, Y g:i A',

    /*
    |--------------------------------------------------------------------------
    | Calendar Date & Time Format
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default date and time format for calendar.
    | m (month), d (day), Y (year), h (hour), i (minute), s (second), A (am/pm)
    | m/d/Y h:i:s A => 12/31/2021 11:59:59 PM
    |
    */
    'calender_date_time_format' => 'm/d/Y H:i:s',

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default date format for your application.
    | F (full month), d (day), Y (year)
    | F d, Y => December 31, 2021
    |
    */
    'date_format' => 'F d, Y ',

    /*
    |--------------------------------------------------------------------------
    | Time Format
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default time format for your application.
    | g (hour), i (minute), A (am/pm)
    | g:i A => 11:59 PM
    |
    */
    'time_format' => 'g:i A',

    /*
    |--------------------------------------------------------------------------
    | ISO Standard Format
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default ISO standard format for your application.
    | Y (year), m (month), d (day), T (time), H (hour), i (minute), s (second), u (microsecond), Z (timezone)
    | Y-m-d\TH:i:s.v\Z => 2021-12-31T23:59:59.000Z
    |
    */

    'iso_standard_format' => 'Y-m-d\TH:i:s.u\Z',

    /*
    |--------------------------------------------------------------------------
    | Pagination Defaults
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default number of rows per page for your application
    ! as well as the maximum allowed for performance reasons,
    | The default value is 20.
    |
    */
    'pagination' => [
        'default' => 12,
        'maximum' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Token Expiration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default password reset token expiration time.
    | The default value is 60 minutes.
    |
    */
    'token_expiration' => 60,

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default OpenAI API configuration for your application.
    |
    */
    'ai_api' => [
        'url' => env('AI_API_URL', 'http://146.190.55.35/api/'),
        'token' => env('AI_API_TOKEN', 'fhsdjfhsdjfdsjhfg'),
        'body_end_point' => 'text-api',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ],
    'settings' => [
        'images' => [
            'email_logo' => 'assets/media/logos/logo-email.png',
            'email_logo_footer' => 'assets/media/logos/logo-email-footer.png',
        ],
        'footer_url' => env('APP_URL'),
        'terms_url' => 'https://www.syllaby.io/terms-and-conditions',
        'privacy_url' => 'https://syllaby.io/privacy-policy',
    ],
];
