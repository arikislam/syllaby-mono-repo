<?php

namespace Tests\Feature\Publisher\Publication\Youtube;

use Carbon\Carbon;
use Mockery\MockInterface;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\AccountPublication;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Services\Youtube\UploadService;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2024-01-10 12:00:00');
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Event::fake(MediaHasBeenAddedEvent::class);
    Bus::fake([PerformConversionsJob::class]);
    Http::fake([
        'https://www.googleapis.com/oauth2/v3/*' => Http::response(['aud' => '1234567890']),
        '*' => Http::response([], 200),
    ]);

    config()->set(['services.youtube.client_id' => '1234567890']);
    config()->set(['services.youtube.developer_key' => '1234567890']);
});

it('can publish a video to a youtube', function () {
    Notification::fake();

    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 2);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->youtube()->create();

    $this->partialMock(UploadService::class, function (MockInterface $mock) {
        $mock->shouldReceive('successful')->andReturn(true);
        $mock->shouldReceive('upload')->once()->andReturn([
            'status' => 'success',
            'response' => [
                'id' => '1234567890', 'status' => ['uploadStatus' => 'uploaded'],
            ],
        ]);
    });

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
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
        ->provider_media_id->toBe('1234567890')
        ->post_type->toBe(PostType::POST)
        ->and($publication->metadata)
        ->title->toBe('This is a test title for youtube video')
        ->description->toBe('This is a test description for youtube video')
        ->privacy_status->toBe('public')
        ->tags->toBe(['test', 'youtube', 'video'])
        ->category->toBe(22)
        ->license->toBe('creativeCommon')
        ->embeddable->toBe(true)
        ->made_for_kids->toBe(false)
        ->notify_subscribers->toBe(true);

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('tries to refresh info if channel information doesnt exist', function () {
    Notification::fake();

    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 2);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->youtube()->create();

    $this->partialMock(UploadService::class, function (MockInterface $mock) {
        $mock->shouldReceive('successful')->andReturn(true);
        $mock->shouldReceive('upload')->once()->andReturn([
            'status' => 'success', 'response' => [
                'id' => '1234567890', 'status' => ['uploadStatus' => 'uploaded'],
            ],
        ]);
    });

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
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
        ->provider_media_id->toBe('1234567890')
        ->post_type->toBe(PostType::POST)
        ->and($publication->metadata)
        ->title->toBe('This is a test title for youtube video')
        ->description->toBe('This is a test description for youtube video')
        ->privacy_status->toBe('public')
        ->tags->toBe(['test', 'youtube', 'video'])
        ->category->toBe(22)
        ->license->toBe('creativeCommon')
        ->embeddable->toBe(true)
        ->made_for_kids->toBe(false)
        ->notify_subscribers->toBe(true);

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('can handle a failed video to Youtube', function () {
    Notification::fake();
    Storage::fake();

    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 2);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->youtube()->create();

    $this->partialMock(UploadService::class, function (MockInterface $mock) {
        $mock->shouldReceive('upload')->once()->andReturn([
            'status' => 'failed', 'errors' => [['message' => 'Some Lorem Ipsum Reason']],
        ]);
    });

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
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
        ->post_type->toBe(PostType::POST)
        ->and($publication->error_message)->toBe('Some Lorem Ipsum Reason')
        ->and($publication->metadata)
        ->title->toBe('This is a test title for youtube video')
        ->description->toBe('This is a test description for youtube video')
        ->privacy_status->toBe('public')
        ->tags->toBe(['test', 'youtube', 'video'])
        ->category->toBe(22)
        ->license->toBe('creativeCommon')
        ->embeddable->toBe(true)
        ->made_for_kids->toBe(false)
        ->notify_subscribers->toBe(true);

    Notification::assertNothingSent();
});

it('can schedule a video to youtube', function () {
    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 2);
    Feature::define('max_scheduled_weeks', 2);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->youtube()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'scheduled_at' => now()->addDay(),
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
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
        ->provider_media_id->toBeNull()
        ->status->toBe(SocialUploadStatus::SCHEDULED->value)
        ->provider_media_id->toBeNull();
});

it('fails to publish to youtube with feature disabled', function () {
    Feature::define('publish_youtube', false);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
    ])->assertForbidden();
});

it('fails to schedule to youtube when maximum scheduled publications is reached', function () {
    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->youtube()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
        'scheduled_at' => now()->addDay(),
    ])->assertForbidden();
});

it('fails to schedule to youtube when scheduled date is out of allowed range', function () {
    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->youtube()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
        'scheduled_at' => now()->addWeeks(3),
    ])->assertForbidden();
});

it('fails to schedule a video in back-date', function () {
    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
        'scheduled_at' => now()->subDay(),
    ])->assertForbidden()->assertJsonPath('message', 'Oops! You can\'t schedule posts in the past. Try picking a future time.'); // Extract this message to a config file for easy changing and testing
});

it('can detach a publication from a youtube channel', function () {
    Notification::fake();

    Feature::define('publish_youtube', true);
    Feature::define('max_scheduled_posts', 2);
    Feature::define('max_scheduled_weeks', 2);

    $date = now()->addDay();
    $user = User::factory()->create();

    $account = SocialAccount::factory()->youtube()->for($user)->createQuietly();
    $channel = SocialChannel::factory()->for($account, 'account')->create();
    $publication = Publication::factory()->scheduled($date)->for($user)->create();

    Media::factory()->for($publication, 'model')->youtube()->create([
        'user_id' => $user->id,
    ]);

    $publication->channels()->attach($channel, [
        'provider_media_id' => '123',
        'post_type' => PostType::POST->value,
        'status' => SocialUploadStatus::SCHEDULED,
    ]);

    $this->assertDatabaseHas('account_publications', [
        'publication_id' => $publication->id,
        'social_channel_id' => $channel->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/publish/youtube', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'scheduled_at' => $date,
        'title' => 'This is a test title for youtube video',
        'description' => 'This is a test description for youtube video',
        'privacy_status' => 'public',
        'tags' => ['test', 'youtube', 'video'],
        'category' => 22,
        'license' => 'creativeCommon',
        'embeddable' => true,
        'made_for_kids' => false,
        'notify_subscribers' => true,
        'detach' => true,
    ]);

    $response->assertNoContent();

    $this->assertDatabaseMissing('account_publications', [
        'publication_id' => $publication->id,
        'social_channel_id' => $channel->id,
    ]);
});
