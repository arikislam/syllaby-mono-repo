<?php

namespace Tests\Feature\Publisher\Metrics;

use Carbon\Carbon;
use Database\Seeders\MetricsTableSeeder;
use App\Syllaby\Publisher\Metrics\AggregateType;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Metrics\PublicationAggregate;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Metrics\Jobs\AggregatePublicationMetricsJob;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2023-01-01 12:00:00');
    $this->seed(MetricsTableSeeder::class);
});

describe('aggregate metrics', function () {
    it('aggregates the latest metrics for each publication and channel', function () {
        $account = SocialAccount::factory()->youtube()->createQuietly();

        $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
            'status' => SocialUploadStatus::COMPLETED,
            'provider_media_id' => '123',
        ])->create();

        $publication = $channel->publications->first();

        $metrics = PublicationMetricKey::where('slug', 'views-count')->orWhere('slug', 'likes-count')->get();

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'publication_metric_key_id' => $metrics->firstWhere('slug', 'views-count')->id,
            'value' => 100,
            'created_at' => now()->subDays(2),
        ]);

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'publication_metric_key_id' => $metrics->firstWhere('slug', 'likes-count')->id,
            'value' => 10,
            'created_at' => now()->subDays(2),
        ]);

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'publication_metric_key_id' => $metrics->firstWhere('slug', 'views-count')->id,
            'value' => 200,
            'created_at' => now()->subDay(),
        ]);

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'publication_metric_key_id' => $metrics->firstWhere('slug', 'likes-count')->id,
            'value' => 20,
            'created_at' => now()->subDay(),
        ]);

        dispatch(new AggregatePublicationMetricsJob);

        $this->assertDatabaseHas('publication_aggregates', [
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'key' => 'views-count',
            'value' => 200,
            'type' => AggregateType::TOTAL->value,
        ]);

        $this->assertDatabaseHas('publication_aggregates', [
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'key' => 'likes-count',
            'value' => 20,
            'type' => AggregateType::TOTAL->value,
        ]);

        $this->assertDatabaseCount('publication_aggregates', 2);
    });

    it('updates existing aggregates instead of creating duplicates', function () {
        $account = SocialAccount::factory()->youtube()->createQuietly();

        $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
            'status' => SocialUploadStatus::COMPLETED,
            'provider_media_id' => '123',
        ])->create();

        $publication = $channel->publications->first();

        $views = PublicationMetricKey::where('slug', 'views-count')->first();

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'publication_metric_key_id' => $views->id,
            'value' => 100,
            'created_at' => now()->subDays(2),
        ]);

        PublicationAggregate::create([
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'key' => 'views-count',
            'value' => 100,
            'type' => AggregateType::TOTAL->value,
            'last_updated_at' => now()->subDays(2),
        ]);

        $this->assertDatabaseCount('publication_aggregates', 1);

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'publication_metric_key_id' => $views->id,
            'value' => 200,
            'created_at' => now()->subDay(),
        ]);

        dispatch(new AggregatePublicationMetricsJob);

        $this->assertDatabaseHas('publication_aggregates', [
            'publication_id' => $publication->id,
            'social_channel_id' => $channel->id,
            'key' => 'views-count',
            'value' => 200,
            'type' => AggregateType::TOTAL->value,
            'last_updated_at' => now(),
        ]);

        $this->assertDatabaseCount('publication_aggregates', 1);
    });

    it('handles multiple publications and channels correctly', function () {
        $account = SocialAccount::factory()->youtube()->createQuietly();

        $channel1 = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
            'status' => SocialUploadStatus::COMPLETED,
            'provider_media_id' => '123',
        ])->create();

        $publication1 = $channel1->publications->first();

        $channel2 = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
            'status' => SocialUploadStatus::COMPLETED,
            'provider_media_id' => '456',
        ])->create();

        $publication2 = $channel2->publications->first();

        $views = PublicationMetricKey::where('slug', 'views-count')->first();

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication1->id,
            'social_channel_id' => $channel1->id,
            'publication_metric_key_id' => $views->id,
            'value' => 100,
            'created_at' => now()->subDay(),
        ]);

        PublicationMetricValue::factory()->create([
            'publication_id' => $publication2->id,
            'social_channel_id' => $channel2->id,
            'publication_metric_key_id' => $views->id,
            'value' => 200,
            'created_at' => now()->subDay(),
        ]);

        dispatch(new AggregatePublicationMetricsJob);

        $this->assertDatabaseHas('publication_aggregates', [
            'publication_id' => $publication1->id,
            'social_channel_id' => $channel1->id,
            'key' => 'views-count',
            'value' => 100,
            'type' => AggregateType::TOTAL->value,
        ]);

        $this->assertDatabaseHas('publication_aggregates', [
            'publication_id' => $publication2->id,
            'social_channel_id' => $channel2->id,
            'key' => 'views-count',
            'value' => 200,
            'type' => AggregateType::TOTAL->value,
        ]);

        $this->assertDatabaseCount('publication_aggregates', 2);
    });
})->skip('Needs some correction based on chunking');