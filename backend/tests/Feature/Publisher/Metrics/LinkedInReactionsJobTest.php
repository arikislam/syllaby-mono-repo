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
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Metrics\Jobs\LinkedInReactionsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Channels\Vendors\Business\LinkedInProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2023-11-01 12:00:00');
});

it('can fetch reactions for LinkedIn Video', function () {
    $this->seed(MetricsTableSeeder::class);

    $this->mock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->withAnyArgs()->andReturn(true);
    });

    Http::fake([
        'https://api.linkedin.com/v2/*' => Http::response([
            'results' => [
                'urn:li:ugcPost:7147687343179677696' => [
                    'likesSummary' => [
                        'totalLikes' => 13,
                    ],
                    'commentsSummary' => [
                        'totalFirstLevelComments' => 1,
                        'aggregatedTotalComments' => 344,
                    ],
                    'target' => 'urn:li:ugcPost:7147687343179677696',
                ],
                'urn:li:ugcPost:7147686314820235264' => [
                    'likesSummary' => [
                        'totalLikes' => 98,
                    ],
                    'commentsSummary' => [
                        'totalFirstLevelComments' => 1,
                        'aggregatedTotalComments' => 45,
                    ],
                    'target' => 'urn:li:ugcPost:7147686314820235264',
                ],
            ],
            'statuses' => [],
            'errors' => [],
        ]),
    ]);

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => 'urn:li:ugcPost:7147687343179677696',
    ])->create();

    $publication2 = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => 'urn:li:ugcPost:7147686314820235264',
    ])->create();

    dispatch(new LinkedInReactionsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications()->first()->id,
        'social_channel_id' => $publication->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 13,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications()->first()->id,
        'social_channel_id' => $publication->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'comments-count')->first()->id,
        'value' => 344,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication2->publications()->first()->id,
        'social_channel_id' => $publication2->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 98,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication2->publications()->first()->id,
        'social_channel_id' => $publication2->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'comments-count')->first()->id,
        'value' => 45,
    ]);
});

it('can handle expired tokens while fetching reactions', function () {
    $this->seed(MetricsTableSeeder::class);

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $this->mock(LinkedInProvider::class, function (MockInterface $mock) use ($account) {
        $mock->shouldReceive('validate')->withAnyArgs()->andReturn(false);
        $mock->shouldReceive('refresh')->withAnyArgs()->andReturn($account);
    });

    Http::fake([
        'https://api.linkedin.com/v2/*' => Http::response([
            'results' => [
                'urn:li:ugcPost:7147687343179677696' => [
                    'likesSummary' => [
                        'totalLikes' => 13,
                    ],
                    'commentsSummary' => [
                        'totalFirstLevelComments' => 1,
                        'aggregatedTotalComments' => 344,
                    ],
                    'target' => 'urn:li:ugcPost:7147687343179677696',
                ],
            ],
            'statuses' => [],
            'errors' => [],
        ]),
    ]);

    $publication = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => 'urn:li:ugcPost:7147687343179677696',
    ])->create();

    dispatch(new LinkedInReactionsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications()->first()->id,
        'social_channel_id' => $publication->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 13,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $publication->publications()->first()->id,
        'social_channel_id' => $publication->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'comments-count')->first()->id,
        'value' => 344,
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

    dispatch(new LinkedInReactionsJob(AccountPublication::all()));

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
            'results' => [
                'urn:li:ugcPost:7147687343179677696' => [
                    'likesSummary' => [
                        'totalLikes' => 13,
                    ],
                    'commentsSummary' => [
                        'totalFirstLevelComments' => 1,
                        'aggregatedTotalComments' => 344,
                    ],
                    'target' => 'urn:li:ugcPost:7147687343179677696',
                ],
            ],
            'statuses' => [],
            'errors' => [],
        ]),
    ]);

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => 'urn:li:ugcPost:7147687343179677696',
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'likes-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    dispatch(new LinkedInReactionsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications()->first()->id,
        'social_channel_id' => $channel->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 13,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications()->first()->id,
        'social_channel_id' => $channel->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
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
            'results' => [
                'urn:li:ugcPost:7147687343179677696' => [
                    'likesSummary' => [
                        'totalLikes' => 13,
                    ],
                    'commentsSummary' => [
                        'totalFirstLevelComments' => 1,
                        'aggregatedTotalComments' => 344,
                    ],
                    'target' => 'urn:li:ugcPost:7147687343179677696',
                ],
            ],
            'statuses' => [],
            'errors' => [],
        ]),
    ]);

    $account = SocialAccount::factory()->linkedin()->createQuietly();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => 'urn:li:ugcPost:7147687343179677696',
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'likes-count')->first(), 'key')
        ->create(['value' => 5000, 'created_at' => now()->subDay()]);

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'likes-count')->first(), 'key')
        ->create(['value' => 10000, 'created_at' => now()->subHour()]);

    dispatch(new LinkedInReactionsJob(AccountPublication::all()));

    $keys = PublicationMetricKey::select(['id', 'slug'])->get();

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications()->first()->id,
        'social_channel_id' => $channel->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 13,
    ]);

    $this->assertDatabaseHas('publication_metric_values', [
        'publication_id' => $channel->publications()->first()->id,
        'social_channel_id' => $channel->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 5000,
    ]);

    $this->assertDatabaseMissing('publication_metric_values', [
        'publication_id' => $channel->publications()->first()->id,
        'social_channel_id' => $channel->publications()->first()->pivot->social_channel_id,
        'publication_metric_key_id' => $keys->where('slug', 'likes-count')->first()->id,
        'value' => 10000,
    ]);
});
