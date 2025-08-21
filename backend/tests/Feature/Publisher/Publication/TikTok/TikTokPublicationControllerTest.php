<?php

namespace Tests\Feature\Publisher\Publication\TikTok;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Support\Facades\Event as IlluminateEvent;
use App\Syllaby\Publisher\Publications\AccountPublication;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Enums\TikTokPrivacyStatus;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Bus::fake(PerformConversionsJob::class);
    IlluminateEvent::fake(MediaHasBeenAddedEvent::class);
    Carbon::setTestNow('2023-01-01 00:00:00');
    Http::fake(['https://open.tiktokapis.com/v2/user/info/?fields=display_name' => Http::response()]);
});

it('can publish a TikTok Post', function () {
    Feature::define('publish_tiktok', true);
    Feature::define('max_scheduled_posts', 2);

    Http::fake([
        'https://open.tiktokapis.com/v2/post/publish/video/init/' => Http::response([
            'data' => ['publish_id' => 'random-publish-id'],
            'error' => ['code' => 'ok'],
        ]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->tiktokShort()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('v1/publish/tiktok', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
    ])->assertAccepted();

    $this->assertDatabaseHas('events', [
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'starts_at' => now(),
        'ends_at' => now(),
        'completed_at' => now(),
    ]);

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => 0,
        'temporary' => 0,
        'video_id' => null,
        'scheduled' => false,
    ]);

    expect($response->json('data.accounts.0'))
        ->toBeArray()
        ->status->toBe(SocialUploadStatus::PROCESSING->value)
        ->metadata->toBeArray()
        ->metadata->publish_id->toBe('random-publish-id')
        ->metadata->caption->toBe('This is a test caption for tiktok video');
});

it('can handle failed video to TikTok', function () {
    Feature::define('publish_tiktok', true);
    Feature::define('max_scheduled_posts', 2);

    Http::fake([
        'https://open.tiktokapis.com/v2/post/publish/video/init/' => Http::response([
            'data' => [],
            'error' => [
                'code' => 'access_token_invalid',
                'message' => 'The access token is invalid or not found in the request.',
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->tiktokShort()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/tiktok', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
    ])->assertServerError();

    $this->assertDatabaseHas('events', [
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'starts_at' => now(),
        'ends_at' => now(),
        'completed_at' => now(),
    ]);

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => 0,
        'temporary' => 0,
        'video_id' => null,
        'scheduled' => false,
    ]);

    expect($publication->channels()->first()->pivot->status)
        ->toBe(SocialUploadStatus::FAILED->value)
        ->and($publication->channels()->first()->pivot->error_message)
        ->toBe('The access token is invalid or not found in the request.');
});

it('can schedule a TikTok post', function () {
    Feature::define('publish_tiktok', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 2);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->tiktokShort()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('v1/publish/tiktok', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'scheduled_at' => now(),
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
    ])->assertAccepted();

    expect($response->json('data'))->scheduled_at->toBe(now()->toJSON());

    $this->assertDatabaseHas(Publication::class, [
        'id' => $publication->id,
    ]);

    $this->assertDatabaseHas('events', [
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'starts_at' => now(),
        'ends_at' => now(),
        'completed_at' => null,
    ]);

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => 0,
        'temporary' => 0,
        'video_id' => null,
        'scheduled' => true,
    ]);

    $this->assertDatabaseHas(AccountPublication::class, [
        'publication_id' => $publication->id,
        'social_channel_id' => $channel->id,
        'status' => SocialUploadStatus::SCHEDULED->value,
    ]);
});

it('fails to publish to tiktok with feature disabled', function () {
    Feature::define('publish_tiktok', false);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/tiktok', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
    ])->assertForbidden();
});

it('fails to schedule to tiktok when maximum scheduled publications is reached', function () {
    Feature::define('publish_tiktok', true);
    Feature::define('max_scheduled_posts', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->hasAttached(Publication::factory()->scheduled(now())->for($user), [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [
                'caption' => 'This is a test caption for tiktok video',
                'allow_comments' => true,
                'allow_duet' => false,
                'allow_stitch' => true,
                'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
            ],
        ])->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/tiktok', [
        'publication_id' => $channel->publications->first()->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
        'scheduled_at' => now()->addDay(),
    ])->assertForbidden();
});

it('fails to schedule to tiktok when scheduled date is out of allowed range', function () {
    Feature::define('publish_tiktok', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->permanent()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/tiktok', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
        'scheduled_at' => now()->addWeeks(3),
    ])->assertForbidden();
});

it('fails to schedule a video in back-date', function () {
    Feature::define('publish_tiktok', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->permanent()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/tiktok', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
        'scheduled_at' => now()->subDay(),
    ])->assertForbidden()->assertJsonPath('message', 'Oops! You can\'t schedule posts in the past. Try picking a future time.'); // Extract this message to a config file for easy changing and testing
});
