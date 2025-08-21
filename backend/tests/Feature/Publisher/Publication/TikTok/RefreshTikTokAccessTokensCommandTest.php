<?php

namespace Tests\Feature\Publisher\Publication\TikTok;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Publisher\Channels\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can refresh tiktok access-token', function () {
    Http::fake([
        'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
            'access_token' => 'new-access-token',
            'expires_in' => 200,
            'refresh_token' => 'new-refresh-token',
            'refresh_expires_in' => 200,
        ]),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->tiktok()->create();

    SocialAccount::factory()->youtube()->for($user)->createQuietly();

    $this->artisan('tiktok:refresh-access-tokens')->assertExitCode(0);

    $this->assertDatabaseCount(SocialAccount::class, 2);
    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $account->id,
        'access_token' => 'new-access-token',
        'expires_in' => 200,
        'refresh_token' => 'new-refresh-token',
        'refresh_expires_in' => 200,
        'needs_reauth' => false,
    ]);

    Http::assertSentCount(1);
});

it('handles correctly in case of expired refresh access tokens', function () {
    Http::fake([
        'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Refresh token is invalid or expired.',
            'log_id' => "202106011200570102040470470D0F1D0",
        ]),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->tiktok()->createQuietly();

    SocialAccount::factory()->for($user)->youtube()->createQuietly();

    $this->artisan('tiktok:refresh-access-tokens')->assertExitCode(0);

    $this->assertDatabaseCount(SocialAccount::class, 2);
    $this->assertDatabaseHas(SocialAccount::class, [
        'id' => $account->id,
        'needs_reauth' => 1,
    ]);

    Http::assertSentCount(1);
});
