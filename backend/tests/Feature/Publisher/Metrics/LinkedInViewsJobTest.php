<?php

namespace Tests\Feature\Publisher\Metrics;

use Carbon\Carbon;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Http;
use Database\Seeders\MetricsTableSeeder;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\Jobs\LinkedInViewsJob;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Channels\Vendors\Individual\LinkedInProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2023-11-01 12:00:00');
});

it('can fetch view count for LinkedIn Organization posts', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->withAnyArgs()->andReturn(true);
    });

    Http::fake([
        'https://api.linkedin.com/v2/*' => Http::response([
            'elements' => [
                [
                    'statisticsType' => 'VIDEO_VIEW',
                    'value' => 3,
                    'entity' => 'urn:li:ugcPost:7147713565540474880',
                    'timeRange' => [],
                ],
            ],
        ]),
    ]);

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $publication = SocialChannel::factory()->organization()
        ->for($account, 'account')
        ->hasAttached(Publication::factory(), [
            'provider_media_id' => 'urn:li:ugcPost:7147713565540474880',
            'status' => SocialUploadStatus::COMPLETED->value,
        ])->create();

    dispatch(new LinkedInViewsJob(AccountPublication::first()));

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => PublicationMetricKey::where('slug', 'views-count')->first()->id,
        'value' => 3,
    ]);
});

it('can handle invalid tokens while fetching metrics', function () {
    $this->mock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturnFalse();
        $mock->shouldReceive('refresh')->andThrow(new InvalidRefreshTokenException);
    });

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    dispatch(new LinkedInViewsJob(AccountPublication::first()));

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
    ]);
});

it('stores analytics as series of event for each day', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->withAnyArgs()->andReturn(true);
    });

    Http::fake([
        'https://api.linkedin.com/v2/*' => Http::response([
            'elements' => [
                [
                    'statisticsType' => 'VIDEO_VIEW',
                    'value' => 3,
                    'entity' => 'urn:li:ugcPost:7147713565540474880',
                    'timeRange' => [],
                ],
            ],
        ]),
    ]);

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $channel = SocialChannel::factory()->organization()->for($account, 'account')->hasAttached(Publication::factory(), [
        'provider_media_id' => 'urn:li:ugcPost:7147713565540474880',
        'status' => SocialUploadStatus::COMPLETED->value,
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    dispatch(new LinkedInViewsJob(AccountPublication::first()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 3,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 5000,
    ]);
});

it('only keeps the latest metrics for each day', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->withAnyArgs()->andReturn(true);
    });

    Http::fake([
        'https://api.linkedin.com/v2/*' => Http::response([
            'elements' => [
                [
                    'statisticsType' => 'VIDEO_VIEW',
                    'value' => 3,
                    'entity' => 'urn:li:ugcPost:7147713565540474880',
                    'timeRange' => [],
                ],
            ],
        ]),
    ]);

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $channel = SocialChannel::factory()->organization()->for($account, 'account')->hasAttached(Publication::factory(), [
        'provider_media_id' => 'urn:li:ugcPost:7147713565540474880',
        'status' => SocialUploadStatus::COMPLETED->value,
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 10000, 'created_at' => now()->subHour()]);

    dispatch(new LinkedInViewsJob(AccountPublication::first()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 3,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 5000,
    ]);

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 10000,
    ]);
});
