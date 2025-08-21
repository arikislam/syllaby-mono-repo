<?php

namespace Tests\Feature\Publisher\Metrics;

use Mockery;
use Carbon\Carbon;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Http;
use Database\Seeders\MetricsTableSeeder;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\Jobs\TikTokMetricsJob;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Channels\Vendors\Individual\TikTokProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2023-01-01 12:00:00');
});

afterEach(function () {
    Mockery::close();
});

it('can fetch metrics for tiktok videos', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(TikTokProvider::class, function ($mock) {
        $mock->shouldReceive('validate')->andReturnTrue();
    });

    Http::fake([
        'https://open.tiktokapis.com/v2/video/*' => Http::response([
            'data' => [
                'videos' => [
                    [
                        'comment_count' => 1,
                        'id' => '7304997804727012613',
                        'like_count' => 12,
                        'share_count' => 10,
                        'view_count' => 113,
                    ],
                ],
                'cursor' => 0,
                'has_more' => false,
            ],
        ]),
    ]);

    $account = SocialAccount::factory()->tiktok()->create();

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => '7304997804727012613',
    ])->create();

    dispatch(new TikTokMetricsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug', 'name'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'views-count')->first()->id,
        'value' => 113,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 12,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'comments-count')->first()->id,
        'value' => 1,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'shares-count')->first()->id,
        'value' => 10,
    ]);
});

it('can handle expired tokens while fetching metrics', function () {
    $this->mock(TikTokProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturnFalse();
        $mock->shouldReceive('refresh')->andThrow(new InvalidRefreshTokenException);
    });

    $account = SocialAccount::factory()->tiktok()->create();

    $post = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => '7304997804727012613',
    ])->create();

    dispatch(new TikTokMetricsJob(AccountPublication::all()));

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $post->publications->first()->id,
        'social_channel_id' => $post->publications->first()->pivot->social_channel_id,
    ]);
});

it('can refresh tokens while fetching metrics', closure: function () {
    $this->seed(MetricsTableSeeder::class);

    Http::fake([
        'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
            'access_token' => 'new-access-token',
            'expires_in' => 2000,
            'refresh_token' => 'new-refresh-token',
            'refresh_expires_in' => 2000,
        ]),
        'https://open.tiktokapis.com/v2/video/query/*' => Http::response([
            'data' => [
                'videos' => [
                    [
                        'comment_count' => 1,
                        'id' => '7304997804727012613',
                        'like_count' => 12,
                        'share_count' => 10,
                        'view_count' => 113,
                    ],
                ],
                'cursor' => 0,
                'has_more' => false,
            ],
        ]),
    ]);

    $mock = Mockery::mock("App\Syllaby\Publisher\Channels\Vendors\Individual\TikTokProvider[validate]");
    $mock->shouldReceive('validate')->andReturnFalse();
    $this->instance(TikTokProvider::class, $mock);

    $account = SocialAccount::factory()->tiktok()->create();

    $post = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => '7304997804727012613',
    ])->create();

    dispatch(new TikTokMetricsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug', 'name'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $post->publications->first()->id,
        'social_channel_id' => $post->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'views-count')->first()->id,
        'value' => 113,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $post->publications->first()->id,
        'social_channel_id' => $post->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 12,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $post->publications->first()->id,
        'social_channel_id' => $post->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'comments-count')->first()->id,
        'value' => 1,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $post->publications->first()->id,
        'social_channel_id' => $post->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'shares-count')->first()->id,
        'value' => 10,
    ]);
});

it('can handle invalid tokens while fetching metrics', function () {
    $this->mock(TikTokProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturnFalse();
        $mock->shouldReceive('refresh')->andThrow(new InvalidRefreshTokenException);
    });

    $account = SocialAccount::factory()->tiktok()->createQuietly();

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    dispatch(new TikTokMetricsJob(AccountPublication::all()));

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $publication->publications->first()->id,
        'social_channel_id' => $publication->publications->first()->pivot->social_channel_id,
    ]);
});

it('stores analytics as series of event for each day', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(TikTokProvider::class, function ($mock) {
        $mock->shouldReceive('validate')->andReturnTrue();
    });

    Http::fake([
        'https://open.tiktokapis.com/v2/video/query/*' => Http::response([
            'data' => [
                'videos' => [
                    [
                        'comment_count' => 1,
                        'id' => '7304997804727012613',
                        'like_count' => 12,
                        'share_count' => 10,
                        'view_count' => 113,
                    ],
                ],
                'cursor' => 0,
                'has_more' => false,
            ],
        ]),
    ]);

    $account = SocialAccount::factory()->tiktok()->create();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => '7304997804727012613',
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    $this->assertDatabaseCount('publication_metric_values', 1);

    dispatch(new TikTokMetricsJob(AccountPublication::all()));

    $this->assertDatabaseCount('publication_metric_values', 5);
});

it('only keeps the latest metrics for each day', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(TikTokProvider::class, function ($mock) {
        $mock->shouldReceive('validate')->andReturnTrue();
    });

    Http::fake([
        'https://open.tiktokapis.com/v2/video/query/*' => Http::response([
            'data' => [
                'videos' => [
                    [
                        'comment_count' => 1,
                        'id' => '7304997804727012613',
                        'like_count' => 12,
                        'share_count' => 10,
                        'view_count' => 113,
                    ],
                ],
                'cursor' => 0,
                'has_more' => false,
            ],
        ]),
    ]);

    $account = SocialAccount::factory()->tiktok()->create();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => '7304997804727012613',
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 2, 'created_at' => now()]);

    $this->assertDatabaseCount('publication_metric_values', 2);

    dispatch(new TikTokMetricsJob(AccountPublication::all()));

    $this->assertDatabaseCount('publication_metric_values', 5);

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
        'value' => 113,
        'created_at' => now(),
    ]);

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $channel->publications->first()->id,
        'social_channel_id' => $channel->publications->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->firstWhere('slug', 'views-count')->id,
        'value' => 2,
        'created_at' => now()->subDay(),
    ]);
});
