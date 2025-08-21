<?php

namespace Tests;

use Tests\Support\FakeFireCrawl;
use Tests\Support\FakeModerator;
use Tests\Support\FakeNewsletter;
use Tests\Support\FakeAbTester;
use Illuminate\Support\Facades\Cache;
use App\Shared\Newsletters\Newsletter;
use App\Syllaby\Analytics\Contracts\AbTester;
use App\Syllaby\Videos\Contracts\ImageModerator;
use App\Syllaby\Scraper\Contracts\ScraperContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    const string FORGET_PASSWORD_TOKEN = 'some-long-random-token-that-will-be-sent-by-email';

    const string NEW_PASSWORD = 'SomethingSuperSecret12!@';

    const string OPEN_AI_MOCKED_RESPONSE = 'Some mocked response from OpenAI';

    const int OPEN_AI_MOCKED_USED_TOKENS = 400;

    const int BASE_MAX_ALLOWED_STORAGE = 5_369_709_120; // 5GB

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->app->bind(AbTester::class, FakeAbTester::class);
        $this->app->bind(Newsletter::class, FakeNewsletter::class);
        $this->app->bind(ScraperContract::class, FakeFireCrawl::class);
        $this->app->bind(ImageModerator::class, FakeModerator::class);
    }
}
