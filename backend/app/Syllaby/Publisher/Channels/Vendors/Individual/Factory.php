<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Individual;

use InvalidArgumentException;

class Factory
{
    public array $providers = [
        'youtube',
        'tiktok',
        'linkedin',
        'facebook',
        'instagram',
        'threads'
    ];

    public function for(string $provider)
    {
        return match ($provider) {
            'youtube' => app(YoutubeProvider::class),
            'tiktok' => app(TikTokProvider::class),
            'linkedin' => app(LinkedInProvider::class),
            'facebook' => app(FacebookProvider::class),
            'instagram' => app(InstagramProvider::class),
            'threads' => app(ThreadsProvider::class),
            default => throw new InvalidArgumentException('Invalid provider'),
        };
    }
}