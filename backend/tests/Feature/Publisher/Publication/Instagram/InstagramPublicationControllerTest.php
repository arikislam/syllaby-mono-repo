<?php

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Carbon;
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
    Bus::fake(PerformConversionsJob::class);
    Event::fake(MediaHasBeenAddedEvent::class);
    Carbon::setTestNow('2023-01-01 00:00:00');
});

it('can publish a reel to instagram', function () {
    Feature::define('publish_instagram', true);

    Notification::fake();

    Http::fake([
        '*' => Http::sequence()
            ->push(['data' => ['is_valid' => true]])
            ->push(['id' => 'video-id'])
            ->push(['status_code' => 'FINISHED', 'id' => 'video-id'])
            ->push(['id' => 'post-id']),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
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
        ->post_type->toBe(PostType::REEL)
        ->provider_media_id->toBe('post-id')
        ->and($publication->metadata)
        ->caption->toBe('This is a caption')
        ->video_id->toBe('video-id')
        ->share_to_feed->toBeTrue();

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('fails to publish with invalid post-type', function () {
    Feature::define('publish_instagram', true);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => 'post',
        'caption' => 'This is a caption',
    ])->assertUnprocessable();
});

it('fails to publish if media is not a reel', function () {
    Feature::define('publish_instagram', true);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
        'caption' => 'This is a caption',
    ])->assertUnprocessable();
});

it('fails to publish reel when user revokes permission', function () {
    Feature::define('publish_instagram', true);

    $user = User::factory()->create();

    Http::fake([
        '*' => Http::sequence()
            ->push(['data' => ['is_valid' => true]])
            ->push(['error' => ['code' => 190, 'error_subcode' => 460]], 400),
    ]);

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
        'caption' => 'This is a caption',
    ])->assertServerError()->assertJsonFragment(['message' => __('publish.lost_permission')]);

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
        ->status->toBe(SocialUploadStatus::FAILED->value)
        ->post_type->toBe(PostType::REEL)
        ->provider_media_id->toBeNull()
        ->and($publication->metadata)
        ->caption->toBe('This is a caption')
        ->video_id->toBeNull();
});

it('fails to publish reel when media is malformed', function () {
    Feature::define('publish_instagram', true);

    $user = User::factory()->create();

    Http::fake([
        '*' => Http::sequence()
            ->push(['data' => ['is_valid' => true]])
            ->push(['id' => 'video-id'])
            ->push(['status_code' => 'ERROR', 'id' => 'video-id']),
    ]);

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
        'caption' => 'This is a caption',
    ])->assertServerError()->assertJsonFragment(['message' => __('publish.malformed_media')]);

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
        ->status->toBe(SocialUploadStatus::FAILED->value)
        ->post_type->toBe(PostType::REEL)
        ->provider_media_id->toBeNull()
        ->and($publication->metadata)
        ->caption->toBe('This is a caption')
        ->video_id->toBeNull();
});

it('can schedule a reel to instagram', function () {
    Feature::define('publish_instagram', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    Http::fake(['*' => Http::response(['data' => ['is_valid' => true]])]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
        'caption' => 'This is a caption',
        'scheduled_at' => now()->addDay(),
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
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    $publication = AccountPublication::where('publication_id', $publication->id)
        ->where('social_channel_id', $channel->id)
        ->first();

    expect($publication)
        ->status->toBe(SocialUploadStatus::SCHEDULED->value)
        ->post_type->toBe(PostType::REEL)
        ->provider_media_id->toBeNull()
        ->and($publication->metadata)
        ->caption->toBe('This is a caption')
        ->video_id->toBeNull();
});

it('fails to publish reel when feature disabled', function () {
    Feature::define('publish_instagram', false);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
        'caption' => 'This is a caption',
    ])->assertForbidden();
});

it('fails to schedule reel when limit is reached', function () {
    Feature::define('publish_instagram', true);
    Feature::define('max_scheduled_posts', 0);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
        'caption' => 'This is a caption',
        'scheduled_at' => now()->addDay(),
    ])->assertForbidden();
});

it('fails to schedule reel when date is out of range', function () {
    Feature::define('publish_instagram', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaReel()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::REEL->value,
        'caption' => 'This is a caption',
        'scheduled_at' => now()->addWeeks(2),
    ])->assertForbidden();
});

it('can publish a story to instagram', function () {
    Feature::define('publish_instagram', true);

    Notification::fake();

    Http::fake([
        '*' => Http::sequence()
            ->push(['id' => 'video-id'])
            ->push(['data' => ['is_valid' => true]])
            ->push(['id' => 'video-id'])
            ->push(['status_code' => 'FINISHED', 'id' => 'video-id'])
            ->push(['id' => 'post-id']),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaStory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
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
        ->post_type->toBe(PostType::STORY)
        ->provider_media_id->toBe('post-id')
        ->and($publication->metadata)
        ->caption->toBe('This is a caption')
        ->video_id->toBe('video-id')
        ->share_to_feed->toBeTrue();

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('fails to publish if media is not a story', function () {
    Feature::define('publish_instagram', true);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
        'caption' => 'This is a caption',
    ])->assertUnprocessable();
});

it('fails to publish story if user doesnt have a business account', function () {
    Feature::define('publish_instagram', true);

    $user = User::factory()->create();

    Http::fake([
        '*' => Http::sequence()->push(['error' => ['code' => 10]], 400),
    ]);

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaStory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
        'caption' => 'This is a caption',
    ])->assertUnprocessable()->assertJsonFragment(['message' => __('publish.incompatible_account')]);

    $this->assertDatabaseMissing('events', [
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
    ]);
});

it('fails to publish story when media is malformed', function () {
    Feature::define('publish_instagram', true);

    $user = User::factory()->create();

    Http::fake([
        '*' => Http::sequence()
            ->push(['id' => 'video-id'])
            ->push(['data' => ['is_valid' => true]])
            ->push(['id' => 'video-id'])
            ->push(['status_code' => 'ERROR', 'id' => 'video-id']),
    ]);

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaStory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
        'caption' => 'This is a caption',
    ])->assertServerError()->assertJsonFragment(['message' => __('publish.malformed_media')]);

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
        ->status->toBe(SocialUploadStatus::FAILED->value)
        ->post_type->toBe(PostType::STORY)
        ->provider_media_id->toBeNull()
        ->and($publication->metadata)
        ->caption->toBe('This is a caption')
        ->video_id->toBeNull();
});

it('can schedule a story to instagram', function () {
    Feature::define('publish_instagram', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    Http::fake(['*' => Http::response(['data' => ['is_valid' => true]])]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaStory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
        'caption' => 'This is a caption',
        'scheduled_at' => now()->addDay(),
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
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    $publication = AccountPublication::where('publication_id', $publication->id)
        ->where('social_channel_id', $channel->id)
        ->first();

    expect($publication)
        ->status->toBe(SocialUploadStatus::SCHEDULED->value)
        ->post_type->toBe(PostType::STORY)
        ->provider_media_id->toBeNull()
        ->and($publication->metadata)
        ->caption->toBe('This is a caption')
        ->video_id->toBeNull();
});

it('fails to publish story when feature disabled', function () {
    Feature::define('publish_instagram', false);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaStory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
        'caption' => 'This is a caption',
    ])->assertForbidden();
});

it('fails to schedule Story when limit is reached', function () {
    Feature::define('publish_instagram', true);
    Feature::define('max_scheduled_posts', 0);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaStory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
        'caption' => 'This is a caption',
        'scheduled_at' => now()->addDay(),
    ])->assertForbidden();
});

it('fails to schedule story when date is out of range', function () {
    Feature::define('publish_instagram', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->professional()
        ->for(SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->instaStory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/instagram', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'post_type' => PostType::STORY->value,
        'caption' => 'This is a caption',
        'scheduled_at' => now()->addWeeks(2),
    ])->assertForbidden();
});
