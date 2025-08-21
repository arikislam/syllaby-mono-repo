<?php

use Carbon\Carbon;
use Mockery\MockInterface;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Channels\Vendors\Individual\TikTokProvider;
use App\Syllaby\Publisher\Channels\Vendors\Individual\LinkedInProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(now());
});

it('can publish scheduled publications', function () {
    Event::fake();
    Notification::fake();

    $this->partialMock(TikTokProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(true);
    });

    Http::fake([
        'https://open.tiktokapis.com/v2/post/publish/video/init/' => Http::response([
            'data' => ['publish_id' => 'random-publish-id'],
            'error' => ['code' => 'ok'],
        ]),
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['value' => ['video' => 'video_data_here']]),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response(['success' => true]),
        'api.linkedin.com/rest/posts' => Http::response([], 201, ['x-restli-id' => 'some-id']),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->push([], 200)
            ->whenEmpty(Http::response([], 404)),
    ]);

    $this->partialMock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(true);
    });

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->scheduled(now())->for($user)->create();

    Media::factory()->linkedInVideo()->for($publication, 'model')->create();

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    $linkedIn = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    Media::factory()->linkedInVideo()->for($publication, 'model')->create();
    Media::factory()->tiktokShort()->for($publication, 'model')->create();

    $this->artisan('publish:scheduled')->assertExitCode(0);

    $this->assertDatabaseHas('events', [
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'completed_at' => now(),
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $tiktok->id,
        'status' => SocialUploadStatus::PROCESSING->value, // Webhook based status
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $linkedIn->id,
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'some-id',
    ]);
});

it('only publish publications scheduled for current minute', function () {
    Event::fake();
    Notification::fake();

    Http::fake([
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['value' => ['video' => 'video_data_here']]),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response(['success' => true]),
        'api.linkedin.com/rest/posts' => Http::response([], 201, ['x-restli-id' => 'some-id']),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->push([], 200)
            ->whenEmpty(Http::response([], 404)),
    ]);

    $this->partialMock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(true);
    });

    $user = User::factory()->create();

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->hasAttached(Publication::factory()->permanent()->scheduled(now()->addHour())->for($user), [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    $linkedIn = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->hasAttached(Publication::factory()->permanent()->scheduled(now())->for($user), [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    Media::factory()->linkedInVideo()->for($linkedIn->publications->first(), 'model')->create();

    $this->artisan('publish:scheduled')->assertExitCode(0);

    $this->assertDatabaseHas('events', [
        'model_id' => $tiktok->publications->first()->id,
        'model_type' => (new Publication)->getMorphClass(),
        'completed_at' => null,
    ]);

    $this->assertDatabaseHas('events', [
        'model_id' => $linkedIn->publications->first()->id,
        'model_type' => (new Publication)->getMorphClass(),
        'completed_at' => now(),
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $tiktok->id,
        'status' => SocialUploadStatus::SCHEDULED->value,
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $linkedIn->id,
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'some-id',
    ]);
});

it('can handle failed scheduled publications', function () {
    Event::fake();
    Notification::fake();

    Http::fake([
        'https://open.tiktokapis.com/v2/post/publish/video/init/' => Http::response([
            'error' => ['code' => 'access_token_invalid', 'message' => 'message'],
        ]),
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['value' => ['video' => 'video_data_here']]),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response(['success' => true]),
        'api.linkedin.com/rest/posts' => Http::response([], 201, ['x-restli-id' => 'some-id']),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->push([], 200)
            ->whenEmpty(Http::response([], 404)),
    ]);

    $this->partialMock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(true);
    });

    $this->partialMock(TikTokProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(true);
    });

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->scheduled(now())->for($user)->create();

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    $linkedIn = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    Media::factory()->tiktokShort()->for($publication, 'model')->create();
    Media::factory()->linkedInVideo()->for($publication, 'model')->create();

    $this->artisan('publish:scheduled')->assertExitCode(0);

    $this->assertDatabaseHas('events', [
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'completed_at' => now(),
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $tiktok->id,
        'status' => SocialUploadStatus::FAILED->value,
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $linkedIn->id,
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'some-id',
    ]);
});

it('can handle invalid/expired token while scheduling publications', function () {
    Event::fake();
    Notification::fake();

    Http::fake([
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['value' => ['video' => 'video_data_here']]),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response(['success' => true]),
        'api.linkedin.com/rest/posts' => Http::response([], 201, ['x-restli-id' => 'some-id']),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->whenEmpty(Http::response([], 404)),
    ]);

    $this->partialMock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(true);
    });

    $this->partialMock(TikTokProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(false);
        $mock->shouldReceive('refresh')->andThrow(new InvalidRefreshTokenException);
    });

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->scheduled(now())->for($user)->create();

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    $linkedIn = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    Media::factory()->tiktokShort()->for($publication, 'model')->create();
    Media::factory()->linkedInVideo()->for($publication, 'model')->create();

    $this->artisan('publish:scheduled')->assertExitCode(0);

    $this->assertDatabaseHas('events', [
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'completed_at' => now(),
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $tiktok->id,
        'status' => SocialUploadStatus::FAILED->value,
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $linkedIn->id,
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'some-id',
    ]);
});

it('can handle already expired tokens while scheduling publications', function () {
    Event::fake();
    Notification::fake();

    Http::fake([
        'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['value' => ['video' => 'video_data_here']]),
        'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response(['success' => true]),
        'api.linkedin.com/rest/posts' => Http::response([], 201, ['x-restli-id' => 'some-id']),
        '*' => Http::sequence()
            ->push(['value' => ['uploadInstructions' => []]])
            ->push([], 200, ['etag' => 'etag_value'])
            ->push([], 200)
            ->whenEmpty(Http::response([], 404)),
    ]);

    $this->partialMock(LinkedInProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(true);
    });

    $this->partialMock(TikTokProvider::class, function (MockInterface $mock) {
        $mock->shouldReceive('validate')->andReturn(false);
    });

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->scheduled(now())->for($user)->create();

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(['needs_reauth' => true]), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    $linkedIn = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::SCHEDULED->value,
            'metadata' => [],
            'post_type' => PostType::POST->value,
        ])->create();

    Media::factory()->tiktokShort()->for($publication, 'model')->create();
    Media::factory()->linkedInVideo()->for($publication, 'model')->create();

    $this->artisan('publish:scheduled')->assertExitCode(0);

    $this->assertDatabaseHas('events', [
        'model_id' => $publication->id,
        'model_type' => $publication->getMorphClass(),
        'completed_at' => now(),
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $tiktok->id,
        'status' => SocialUploadStatus::FAILED->value,
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $linkedIn->id,
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'some-id',
    ]);
});
