<?php

use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

return [
    SocialAccountEnum::Youtube->toString() => [
        'title' => 'Youtube',
        'type' => 'Channel',
        'icon' => 'assets/social-logos/youtube.svg',
    ],

    SocialAccountEnum::TikTok->toString() => [
        'title' => 'TikTok',
        'type' => 'Account',
        'icon' => 'assets/social-logos/tiktok.svg',
    ],

    SocialAccountEnum::LinkedIn->toString() => [
        'title' => 'LinkedIn',
        'type' => 'Account',
        'icon' => 'assets/social-logos/linkedin.svg',
    ],

    SocialAccountEnum::Facebook->toString() => [
        'title' => 'Facebook',
        'type' => 'Page',
        'icon' => 'assets/social-logos/facebook.svg',
    ],

    SocialAccountEnum::Instagram->toString() => [
        'title' => 'Instagram',
        'type' => 'Professional Account',
        'icon' => 'assets/social-logos/instagram.svg'
    ],

    SocialAccountEnum::Threads->toString() => [
        'title' => 'Threads',
        'type' => 'Account',
        'icon' => 'assets/social-logos/threads.svg'
    ],
];