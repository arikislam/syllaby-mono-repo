<?php

namespace Tests\Feature\Publisher\Metrics;

use Mockery;
use Carbon\Carbon;
use Google\Client;
use Mockery\MockInterface;
use Google\Service\YouTube\Video;
use Database\Seeders\MetricsTableSeeder;
use Google\Service\YouTube\VideoListResponse;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\Jobs\YoutubeMetricsJob;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Metrics\Services\YoutubeMetricsService;
use App\Syllaby\Publisher\Channels\Vendors\Individual\YoutubeProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2023-01-01 12:00:00');
});

afterEach(function () {
    Mockery::close();
});

it('can fetch metrics for Youtube Videos', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(YoutubeProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->withAnyArgs()->andReturn(true);
    });

    $this->partialMock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAccessToken')->withAnyArgs()->andReturnSelf();
    });

    $this->mock(YoutubeMetricsService::class, function (MockInterface $mock) {
        $mock->shouldReceive('fetch')->withAnyArgs()->andReturn(new VideoListResponse([
            'items' => [
                new Video([
                    'id' => '123',
                    'statistics' => [
                        'viewCount' => 100,
                        'likeCount' => 10,
                        'commentCount' => 5,
                        'dislikeCount' => 27,
                        'favoriteCount' => 1,
                    ],
                ]),
            ],
        ]));
    });

    $account = SocialAccount::factory()->youtube()->createQuietly();

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    dispatch(new YoutubeMetricsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'views-count')->first()->id,
        'value' => 100,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 10,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'comments-count')->first()->id,
        'value' => 5,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'dislikes-count')->first()->id,
        'value' => 27,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'favorites-count')->first()->id,
        'value' => 1,
    ]);
});

it('can handle invalid tokens while fetching metrics', function () {
    $this->mock(YoutubeProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturnFalse();
        $mock->shouldReceive('refresh')->andThrow(new InvalidRefreshTokenException);
    });

    $account = SocialAccount::factory()->youtube()->createQuietly();

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    dispatch(new YoutubeMetricsJob(AccountPublication::all()));

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
    ]);
});

it('can refresh tokens while fetching metrics', function () {
    $this->seed(MetricsTableSeeder::class);

    $account = SocialAccount::factory()->youtube()->createQuietly();

    $this->partialMock(YoutubeProvider::class, function (MockInterface $mock) use ($account) {
        $mock->shouldReceive('validate')->andReturnFalse();
        $mock->shouldReceive('refresh')->andReturn($account);
    });

    $this->mock(YoutubeMetricsService::class, function (MockInterface $mock) {
        $mock->shouldReceive('fetch')->withAnyArgs()->andReturn(new VideoListResponse([
            'items' => [
                new Video([
                    'id' => '123',
                    'statistics' => [
                        'viewCount' => 100,
                        'likeCount' => 10,
                        'commentCount' => 5,
                        'dislikeCount' => 27,
                        'favoriteCount' => 1,
                    ],
                ]),
            ],
        ]));
    });

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    dispatch(new YoutubeMetricsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'views-count')->first()->id,
        'value' => 100,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 10,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'comments-count')->first()->id,
        'value' => 5,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'dislikes-count')->first()->id,
        'value' => 27,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'favorites-count')->first()->id,
        'value' => 1,
    ]);
});

it('stores analytics as series of event for each day', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(YoutubeProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturnTrue();
    });

    $this->partialMock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAccessToken')->withAnyArgs()->andReturnSelf();
    });

    $this->mock(YoutubeMetricsService::class, function (MockInterface $mock) {
        $mock->shouldReceive('fetch')->withAnyArgs()->andReturn(new VideoListResponse([
            'items' => [
                new Video([
                    'id' => '123',
                    'statistics' => [
                        'viewCount' => 100,
                        'likeCount' => 10,
                        'commentCount' => 5,
                        'dislikeCount' => 27,
                        'favoriteCount' => 1,
                    ],
                ]),
            ],
        ]));
    });

    $account = SocialAccount::factory()->youtube()->createQuietly();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    $this->assertDatabaseCount('publication_metric_values', 1);

    dispatch(new YoutubeMetricsJob(AccountPublication::all()));

    $this->assertDatabaseCount('publication_metric_values', 6);
});

it('only keeps the latest metrics for each day', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(YoutubeProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturnTrue();
    });

    $this->partialMock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAccessToken')->withAnyArgs()->andReturnSelf();
    });

    $this->mock(YoutubeMetricsService::class, function (MockInterface $mock) {
        $mock->shouldReceive('fetch')->withAnyArgs()->andReturn(new VideoListResponse([
            'items' => [
                new Video([
                    'id' => '123',
                    'statistics' => [
                        'viewCount' => 100,
                        'likeCount' => 10,
                        'commentCount' => 5,
                        'dislikeCount' => 27,
                        'favoriteCount' => 1,
                    ],
                ]),
            ],
        ]));
    });

    $account = SocialAccount::factory()->youtube()->createQuietly();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 10000, 'created_at' => now()]);

    $this->assertDatabaseCount('publication_metric_values', 2);

    dispatch(new YoutubeMetricsJob(AccountPublication::all()));

    $this->assertDatabaseCount('publication_metric_values', 6);

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 5000,
        'created_at' => now()->subDay(),
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 100,
        'created_at' => now(),
    ]);

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 10000,
        'created_at' => now(),
    ]);
});
