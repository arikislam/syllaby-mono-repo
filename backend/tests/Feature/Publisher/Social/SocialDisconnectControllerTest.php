<?php

namespace Tests\Feature\Publisher\Social;

use App\Syllaby\Planner;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Database\Seeders\MetricsTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can disconnect a youtube account if access token is valid', function () {
    Event::fake();

    Http::fake(['https://accounts.google.com/o/oauth2/revoke' => Http::response()]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->youtube()->recycle($user)
        ->has(SocialChannel::factory()->individual(), 'channels')
        ->createQuietly();

    $this->actingAs($user)->postJson('/v1/social/disconnect/youtube', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $account->id]);
    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $account->channels->first()->id]);
});

it('can disconnect a youtube account if access token is in-valid', function () {
    Event::fake();

    Http::fake([
        'https://accounts.google.com/o/oauth2/revoke' => Http::response([
            'error' => 'invalid_token',
        ], 400),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->youtube()->for($user)
        ->has(SocialChannel::factory()->individual(), 'channels')
        ->createQuietly();

    $this->actingAs($user)->postJson('/v1/social/disconnect/youtube', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $account->id]);
    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $account->channels->first()->id]);
});

it('can disconnect a tiktok account if access token is valid', function () {
    Http::fake([
        'https://open.tiktokapis.com/v2/oauth/revoke/' => Http::response([]),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->tiktok()->for($user)
        ->has(SocialChannel::factory()->individual(), 'channels')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/tiktok', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $account->id]);
    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $account->channels->first()->id]);
});

it('can disconnect a tiktok account if access token is in-valid', function () {
    Http::fake([
        'https://open.tiktokapis.com/v2/oauth/revoke/' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Access token is invalid or expired.',
            'log_id' => '202308181446020224D7E8A73378155C3F',
        ]),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->tiktok()->for($user)
        ->has(SocialChannel::factory()->individual(), 'channels')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/tiktok', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $account->id]);
    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $account->channels->first()->id]);
});

it('can disconnect a linkedin account if access token is valid', function () {
    Http::fake([
        'https://www.linkedin.com/oauth/v2/revoke' => Http::response(),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->linkedin()->for($user)
        ->has(SocialChannel::factory()->individual(), 'channels')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/linkedin', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $account->id]);
    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $account->channels->first()->id]);
});

it('can disconnect a linkedin account if access token is in-valid', function () {
    Http::fake([
        'https://www.linkedin.com/oauth/v2/revoke' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'The provided authorization grant or refresh token is invalid, expired or revoked.',
        ], 400),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->linkedin()->for($user)
        ->has(SocialChannel::factory()->individual(), 'channels')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/linkedin', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $account->id]);
    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $account->channels->first()->id]);
});

it('can disconnect a facebook account if access-token is valid', function () {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->facebook()->for($user)
        ->has(SocialChannel::factory()->page(), 'channels')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/facebook', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, [
        'id' => $account->id,
    ]);

    $this->assertDatabaseMissing(SocialChannel::class, [
        'id' => $account->channels->first()->id,
    ]);
});

it('can disconnect a facebook account if access-token is already expired or revoked', function () {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'error' => ['code' => 190], // 190 is expired/revoked/invalid in case of Meta
        ], 400),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->facebook()->for($user)
        ->has(SocialChannel::factory()->page(), 'channels')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/facebook', [
        'id' => $account->channels->first()->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialAccount::class, [
        'id' => $account->id,
    ]);

    $this->assertDatabaseMissing(SocialChannel::class, [
        'id' => $account->channels->first()->id,
    ]);
});

it('disconnect one linkedin organization if multiple organizations are connected', function () {
    Http::fake(['https://accounts.google.com/o/oauth2/revoke' => Http::response()]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->linkedin()->for($user)
        ->has(SocialChannel::factory()->individual(), 'channels')
        ->has(SocialChannel::factory()->organization(), 'channels')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/linkedin', [
        'id' => $account->channels->firstWhere('type', SocialChannel::ORGANIZATION)->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialChannel::class, [
        'id' => $account->channels->firstWhere('type', SocialChannel::ORGANIZATION)->id,
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'id' => $account->channels->firstWhere('type', SocialChannel::INDIVIDUAL)->id,
    ]);

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $account->id,
        'access_token' => $account->access_token,
        'refresh_token' => $account->refresh_token,
        'provider' => SocialAccountEnum::LinkedIn->value,
    ]);
});

it('deletes publication and event when a channel is disconnected', function () {
    Http::fake(['*' => Http::response()]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->youtube()->createQuietly();

    $publication = Publication::factory()->for($user)->create();

    $event = Planner\Event::factory()->for($publication, 'model')->create();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED,
    ])->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/linkedin', [
        'id' => $channel->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $channel->id]);
    $this->assertDatabaseMissing(Publication::class, ['id' => $publication->id]);
    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $account->id]);
    $this->assertDatabaseMissing(Planner\Event::class, ['id' => $event->id]);
    $this->assertDatabaseMissing(AccountPublication::class, [
        'social_channel_id' => $channel->id,
        'publication_id' => $publication->id,
    ]);
});

it('detaches the channel if publication is bound to multiple channels', function () {
    Http::fake(['*' => Http::response()]);

    $user = User::factory()->create();

    $publication = Publication::factory()->for($user)->create();

    $event = Planner\Event::factory()->for($publication, 'model')->create();

    $youtube = SocialChannel::factory()->individual()
        ->for($youtubeAccount = SocialAccount::factory()->for($user)->youtube()->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::COMPLETED,
        ])->create();

    $linkedin = SocialChannel::factory()->organization()
        ->for($linkedInOrg = SocialAccount::factory()->for($user)->linkedin()->createQuietly(), 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::COMPLETED,
        ])->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/linkedin', [
        'id' => $linkedin->id,
    ])->assertNoContent();

    $this->assertDatabaseHas(SocialChannel::class, ['id' => $youtube->id]);
    $this->assertDatabaseHas(Publication::class, ['id' => $publication->id]);
    $this->assertDatabaseHas(SocialAccount::class, ['id' => $youtubeAccount->id]);
    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $linkedin->id]);
    $this->assertDatabaseMissing(SocialAccount::class, ['id' => $linkedInOrg->id]);
    $this->assertDatabaseHas(Planner\Event::class, ['id' => $event->id]);
    $this->assertDatabaseHas(AccountPublication::class, [
        'social_channel_id' => $youtube->id,
        'publication_id' => $publication->id,
    ]);

    $this->assertDatabaseMissing(AccountPublication::class, [
        'social_channel_id' => $linkedin->id,
        'publication_id' => $publication->id,
    ]);
});

it('removes the metrics of a channel when disconnected', function () {
    Http::fake(['*' => Http::response()]);

    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->tiktok()->for($user)->create();

    $channel = SocialChannel::factory()->for($account, 'account')->hasAttached(Publication::factory(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => '7304997804727012613',
    ])->create();

    PublicationMetricValue::factory()->for($channel, 'channel')
        ->for($channel->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000]);

    $this->actingAs($user)->postJson('/v1/social/disconnect/linkedin', [
        'id' => $channel->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(PublicationMetricValue::class, [
        'social_channel_id' => $channel->id,
        'value' => 5000,
    ]);
});

it('just deletes the channel keeping account if multiple channels are connected', function () {
    Http::fake(['*' => Http::response()]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->linkedin()->createQuietly();

    $publication = Publication::factory()->for($user)->create();

    $event = Planner\Event::factory()->for($publication, 'model')->create();

    $channel = SocialChannel::factory()->for($account, 'account')->individual()->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
    ])->create();

    $organization = SocialChannel::factory()->organization()
        ->for($account, 'account')
        ->for($account, 'account')
        ->hasAttached($publication, [
            'status' => SocialUploadStatus::COMPLETED->value,
        ])->create();

    $this->actingAs($user)->postJson('/v1/social/disconnect/linkedin', [
        'id' => $channel->id,
    ])->assertNoContent();

    $this->assertDatabaseMissing(SocialChannel::class, ['id' => $channel->id]);
    $this->assertDatabaseHas(SocialChannel::class, ['id' => $organization->id]);
    $this->assertDatabaseHas(Publication::class, ['id' => $publication->id]);
    $this->assertDatabaseHas(Planner\Event::class, ['id' => $event->id]);
    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $account->id,
        'access_token' => $account->access_token,
        'refresh_token' => $account->refresh_token,
    ]);
});
