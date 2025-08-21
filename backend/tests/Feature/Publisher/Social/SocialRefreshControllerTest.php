<?php

namespace Tests\Feature\Publisher\Social;

use Mockery;
use Carbon\Carbon;
use Google\Client;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can successfully refresh a youtube account when refresh token is valid', function () {
    Event::fake();

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $mock = Mockery::mock(Client::class);

    $mock->shouldReceive('setClientId')->once()->andReturnSelf();
    $mock->shouldReceive('setClientSecret')->once()->andReturnSelf();

    $mock->shouldReceive('fetchAccessTokenWithRefreshToken')
        ->with($channel->account->refresh_token)
        ->once()
        ->andReturn([
            'access_token' => 'new-access-token',
            'expires_in' => 200,
            'refresh_token' => 'new-refresh-token',
        ]);

    $this->instance(Client::class, $mock);

    $this->actingAs($user)->postJson('/v1/social/refresh/youtube', [
        'id' => $channel->id,
    ])->assertOk();

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'access_token' => 'new-access-token',
        'expires_in' => 200,
        'refresh_token' => 'new-refresh-token',
        'needs_reauth' => 0,
    ]);
});

it('cant refresh a youtube account when refresh token is invalid', function () {
    Event::fake();

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account')
        ->create();

    $mock = Mockery::mock(Client::class);

    $mock->shouldReceive('setClientId')->once()->andReturnSelf();
    $mock->shouldReceive('setClientSecret')->once()->andReturnSelf();

    $mock->shouldReceive('fetchAccessTokenWithRefreshToken')->with($channel->account->refresh_token)
        ->once()
        ->andReturn([
            'error' => 'invalid_grant',
            'error_description' => 'Bad Request',
        ]);

    $this->instance(Client::class, $mock);

    $response = $this->actingAs($user)->postJson('/v1/social/refresh/youtube', [
        'id' => $channel->id,
    ])->assertServerError();

    expect($response->json('message'))->toBe(__('social.refresh_failed', ['provider' => 'youtube']));

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'needs_reauth' => 1,
    ]);
});

it('successfully refresh a tiktok account when refresh token is valid ', function () {
    Http::fake([
        'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
            'access_token' => 'new-access-token',
            'expires_in' => 200,
            'refresh_token' => 'new-refresh-token',
            'refresh_expires_in' => 200,
        ]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/refresh/tiktok', [
        'id' => $channel->id,
    ])->assertOk();

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'access_token' => 'new-access-token',
        'expires_in' => 200,
        'refresh_token' => 'new-refresh-token',
        'refresh_expires_in' => 200,
        'needs_reauth' => 0,
    ]);
});

it('cant refresh a tiktok account when refresh token is invalid ', function () {
    Http::fake([
        'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Refresh token is invalid or expired.',
            'log_id' => '202106011200570102040470470D0F1D0',
        ]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account')
        ->create();

    $response = $this->actingAs($user)->postJson('/v1/social/refresh/tiktok', [
        'id' => $channel->id,
    ])->assertServerError();

    expect($response->json('message'))->toBe(__('social.refresh_failed', ['provider' => 'tiktok']));

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'needs_reauth' => 1,
    ]);
});

it('successfully refresh a linkedin account when refresh token is valid ', function () {
    Http::fake([
        'https://www.linkedin.com/oauth/v2/accessToken' => Http::response([
            'access_token' => 'new-access-token',
            'expires_in' => 200,
            'refresh_token' => 'new-refresh-token',
            'refresh_token_expires_in' => 200,
        ]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/refresh/linkedin', [
        'id' => $channel->id,
    ])->assertOk();

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'access_token' => 'new-access-token',
        'expires_in' => 200,
        'refresh_token' => 'new-refresh-token',
        'refresh_expires_in' => 200,
        'needs_reauth' => 0,
    ]);
});

it('successfully refresh a linkedin organization when refresh token is valid ', function () {
    Http::fake([
        'https://www.linkedin.com/oauth/v2/accessToken' => Http::response([
            'access_token' => 'new-access-token',
            'expires_in' => 200,
            'refresh_token' => 'new-refresh-token',
            'refresh_token_expires_in' => 200,
        ]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->organization()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/refresh/linkedin', [
        'id' => $channel->id,
    ])->assertOk();

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'access_token' => 'new-access-token',
        'expires_in' => 200,
        'refresh_token' => 'new-refresh-token',
        'refresh_expires_in' => 200,
        'needs_reauth' => 0,
    ]);
});

it('cant refresh a linkedin account when refresh token is invalid ', function () {
    Http::fake([
        'https://www.linkedin.com/oauth/v2/accessToken' => Http::response([
            'error' => 'invalid_request',
            'error_description' => 'The provided authorization grant or refresh token is invalid, expired or revoked',
        ], 400),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $response = $this->actingAs($user)->postJson('/v1/social/refresh/linkedin', [
        'id' => $channel->id,
    ])->assertServerError();

    expect($response->json('message'))->toBe(__('social.refresh_failed', ['provider' => 'linkedin']));

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'needs_reauth' => 1,
    ]);
});

it('can refresh a threads account', function () {
    Http::fake([
        'https://graph.threads.net/*' => Http::response([
            'access_token' => 'new-access-token',
            'expires_in' => 200,
        ]),
    ]);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account')
        ->create();

    $this->actingAs($user)->postJson('/v1/social/refresh/threads', [
        'id' => $channel->id,
    ])->assertOk();

    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $channel->social_account_id,
        'access_token' => 'new-access-token',
        'expires_in' => 200,
        'needs_reauth' => 0,
    ]);
});

it('cant refresh a threads account if access token is expired', function () {
    $user = User::factory()->create();

    $channel = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account')
        ->create();

    Carbon::setTestNow(Carbon::now()->addMonth());

    $this->actingAs($user)->postJson('/v1/social/refresh/threads', [
        'id' => $channel->id,
    ])->assertServerError()->assertJsonPath('message', __('social.refresh_failed', ['provider' => 'threads']));
});
