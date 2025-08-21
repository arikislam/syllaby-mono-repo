<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use InvalidArgumentException;
use Illuminate\Support\Manager;

class PublishManager extends Manager
{
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No default driver set.');
    }

    public function createYoutubeDriver(): AbstractProvider
    {
        return app(YoutubeProvider::class);
    }

    public function createLinkedinDriver(): AbstractProvider
    {
        return app(LinkedInProvider::class);
    }

    public function createTiktokDriver(): AbstractProvider
    {
        return app(TikTokProvider::class);
    }

    public function createFacebookDriver(): AbstractProvider
    {
        return app(FacebookProvider::class);
    }

    public function createInstagramDriver(): AbstractProvider
    {
        return app(InstagramProvider::class);
    }

    public function createThreadsDriver(): AbstractProvider
    {
        return app(ThreadsProvider::class);
    }
}
