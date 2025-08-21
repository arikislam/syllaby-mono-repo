<?php

namespace Tests\Feature\Publisher\Publication\LinkedIn;

use Carbon\Carbon;
use Mockery\MockInterface;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
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
use App\Syllaby\Publisher\Channels\Vendors\Individual\LinkedInProvider;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Bus::fake(PerformConversionsJob::class);
    Event::fake(MediaHasBeenAddedEvent::class);
    Notification::fake();
    Carbon::setTestNow('2023-01-01 00:00:00');
});

it('can publish a post to LinkedIn individual account', function () {
    Feature::define('publish_linkedin', true);
    Feature::define('max_scheduled_posts', 2);

    Http::fake([
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['value' => ['video' => 'video_data_here']]),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response(['success' => true]),
        'api.linkedin.com/rest/posts' => Http::response([], 201, ['x-restli-id' => 'some-id']),
        'api.linkedin.com/v2/*' => Http::response([]),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->push([], 200)
            ->whenEmpty(Http::response([], 404)),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->linkedInVideo()->create();

    $this->actingAs($user)->postJson('v1/publish/linkedin', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'visibility' => 'CONNECTIONS',
        'caption' => 'Test caption',
        'title' => 'Test title',
    ])->assertAccepted();

    $this->assertDatabaseHas(Publication::class, [
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
        ->post_type->toBe(PostType::POST)
        ->provider_media_id->toBe('some-id')
        ->social_channel_id->toBe($channel->id)
        ->and($publication->metadata)
        ->caption->toBe('Test caption')
        ->title->toBe('Test title')
        ->visibility->toBe('CONNECTIONS');

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('can handle a failed post on LinkedIn', function () {
    Feature::define('publish_linkedin', true);
    Feature::define('max_scheduled_posts', 2);

    Http::fake([
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response([], 400),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response([], 400),
        'api.linkedin.com/rest/posts' => Http::response([], 400),
        'api.linkedin.com/v2/*' => Http::response([]),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->whenEmpty(Http::response([], 404)),
    ]);

    $this->partialMock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->once()->andReturn(true);
    });

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->linkedInVideo()->create();

    $this->actingAs($user)->postJson('v1/publish/linkedin', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'visibility' => 'CONNECTIONS',
        'caption' => 'Test caption',
        'title' => 'Test title',
    ])->assertAccepted();

    $this->assertDatabaseHas(Publication::class, [
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
        ->post_type->toBe(PostType::POST)
        ->provider_media_id->toBeNull()
        ->social_channel_id->toBe($channel->id)
        ->and($publication->metadata)
        ->caption->toBe('Test caption')
        ->title->toBe('Test title')
        ->visibility->toBe('CONNECTIONS');

    Notification::assertNotSentTo($user, PublicationSuccessful::class);
});

it('can publish a post to LinkedIn Organization account', function () {
    Feature::define('publish_linkedin', true);
    Feature::define('max_scheduled_posts', 2);

    Http::fake([
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['value' => ['video' => 'video_data_here']]),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response(['success' => true]),
        'api.linkedin.com/rest/posts' => Http::response([], 201, ['x-restli-id' => 'some-id']),
        'api.linkedin.com/v2/*' => Http::response([]),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->push([], 200)
            ->whenEmpty(Http::response([], 404)),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->organization()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->linkedInVideo()->create();

    $this->actingAs($user)->postJson('v1/publish/linkedin', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'visibility' => 'CONNECTIONS',
        'caption' => 'Test caption',
        'title' => 'Test title',
    ])->assertAccepted();

    $this->assertDatabaseHas(Publication::class, [
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
        ->post_type->toBe(PostType::POST)
        ->provider_media_id->toBe('some-id')
        ->social_channel_id->toBe($channel->id)
        ->and($publication->metadata)
        ->caption->toBe('Test caption')
        ->title->toBe('Test title')
        ->visibility->toBe('CONNECTIONS');

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('can schedule a post on LinkedIn', function () {
    Feature::define('publish_linkedin', true);
    Feature::define('max_scheduled_posts', 2);
    Feature::define('max_scheduled_weeks', 2);

    Http::fake([
        'api.linkedin.com/v2/*' => Http::response([]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    Media::factory()->for($publication, 'model')->linkedInVideo()->create();

    $this->actingAs($user)->postJson('v1/publish/linkedin', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'scheduled_at' => now()->addDay(),
        'visibility' => 'CONNECTIONS',
        'caption' => 'Test caption',
        'title' => 'Test title',
    ])->assertAccepted();

    $this->assertDatabaseHas(Publication::class, [
        'id' => $publication->id,
        'draft' => 0,
        'temporary' => 0,
        'video_id' => null,
        'scheduled' => true,
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
        ->provider_media_id->toBeNull();
});

it('fails to schedule a video in back-date', function () {
    Feature::define('publish_linkedin', true);
    Feature::define('max_scheduled_posts', 1);
    Feature::define('max_scheduled_weeks', 1);

    Http::fake([
        'api.linkedin.com/v2/*' => Http::response([]),
        '*' => Http::response([], 200),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->for($user)->create();

    $this->actingAs($user)->postJson('v1/publish/linkedin', [
        'publication_id' => $publication->id,
        'channel_id' => $channel->id,
        'scheduled_at' => now()->subDay(),
        'visibility' => 'CONNECTIONS',
        'caption' => 'Test caption',
        'title' => 'Test title',
    ])->assertForbidden()->assertJsonPath('message', 'Oops! You can\'t schedule posts in the past. Try picking a future time.'); // Extract this message to a config file for easy changing and testing
});

it('can publish actual post to LinkedIn', function () {
    $user = User::factory()->create();

    $linkedin = SocialAccount::factory()->linkedin()->create(['provider_id' => '<id>', 'user_id' => $user->id, 'access_token' => '<token>']);

    $linkedInOrg = SocialChannel::factory()->organization()->create(['social_account_id' => $linkedin->id, 'provider_id' => '<org-id>']);

    $publication = Publication::factory()->create(['user_id' => $user->id]);

    $publication->addMedia(public_path('test/test.mp4'))->preservingOriginal()->addCustomHeaders(['ACL' => 'public-read'])->toMediaCollection('publications');

    $this->actingAs($user)->postJson('v1/publish/linkedin', [
        'publication_id' => $publication->id,
        'channel_id' => $linkedInOrg->id,
        'visibility' => 'CONNECTIONS',
        'caption' => 'This is actual testing without thumbnail',
        'title' => 'Title for testing',
    ])->dd();
})->skip('This test is for actual testing');
