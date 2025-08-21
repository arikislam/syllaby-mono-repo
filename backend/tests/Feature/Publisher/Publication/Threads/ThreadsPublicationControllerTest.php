<?php

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Sleep;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\AccountPublication;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Event::fake(MediaHasBeenAddedEvent::class);
    Bus::fake(PerformConversionsJob::class);
    Carbon::setTestNow('2024-01-01 00:00:00');
});

it('can publish a video to threads', function () {
    Feature::define('publish_threads', true);

    Notification::fake();
    Sleep::fake();

    Http::fake([
        '*' => Http::sequence()
            ->push(['id' => 'creation-id'])
            ->push(['id' => 'post-id']),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->threadsPost()->create();

    $this->actingAs($user)->postJson('v1/publish/threads', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a caption',
    ])->assertAccepted();

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => 0,
        'temporary' => 0,
        'video_id' => null,
        'scheduled' => 0,
    ]);

    $this->assertDatabaseHas('events', [
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'starts_at' => now(),
        'ends_at' => now(),
        'completed_at' => now(),
    ]);

    $publication = AccountPublication::where('publication_id', $publication->id)
        ->where('social_channel_id', $channel->id)
        ->first();

    expect($publication)
        ->status->toBe(SocialUploadStatus::COMPLETED->value)
        ->post_type->toBe(PostType::POST)
        ->provider_media_id->toBe('post-id')
        ->and($publication->metadata)
        ->caption->toBe('This is a caption');

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('cant publish to thread if feature is disabled', function () {
    Feature::define('publish_threads', false);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->threadsPost()->create();

    $this->actingAs($user)->postJson('v1/publish/threads', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a caption',
    ])->assertForbidden();
});

it('can schedule a post to threads', function () {
    Feature::define('publish_threads', true);
    Feature::define('max_scheduled_posts', 2);
    Feature::define('max_scheduled_weeks', 2);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->threadsPost()->create();

    $this->actingAs($user)->postJson('v1/publish/threads', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a caption',
        'scheduled_at' => '2024-01-01 01:00:00',
    ])->assertAccepted();

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => 0,
        'temporary' => 0,
        'video_id' => null,
        'scheduled' => 1,
    ]);

    $this->assertDatabaseHas('events', [
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'starts_at' => '2024-01-01 01:00:00',
        'ends_at' => '2024-01-01 01:00:00',
        'completed_at' => null,
    ]);

    $publication = AccountPublication::where('publication_id', $publication->id)
        ->where('social_channel_id', $channel->id)
        ->first();

    expect($publication)
        ->status->toBe(SocialUploadStatus::SCHEDULED->value)
        ->post_type->toBe(PostType::POST)
        ->provider_media_id->toBeNull()
        ->and($publication->metadata)
        ->caption->toBe('This is a caption');
});

it('cant schedule post when limit is reached', function () {
    Feature::define('publish_threads', true);
    Feature::define('max_scheduled_posts', 0);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    $this->actingAs($user)->postJson('v1/publish/threads', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a caption',
        'scheduled_at' => '2024-01-01 01:00:00',
    ])->assertForbidden();
});

it('cant schedule a post when date is out of range', function () {
    Feature::define('publish_threads', true);
    Feature::define('max_scheduled_posts', 2);
    Feature::define('max_scheduled_weeks', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    $this->actingAs($user)->postJson('v1/publish/threads', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'caption' => 'This is a caption',
        'scheduled_at' => '2024-01-15 01:00:00',
    ])->assertForbidden();
});
