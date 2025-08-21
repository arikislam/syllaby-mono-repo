<?php

namespace Tests\Feature\Publisher\Social;

use App\Syllaby\Users\User;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can create a redirect URL for social-accounts', function () {
    $user = User::factory()->create();

    Socialite::shouldReceive('driver->stateless->setScopes->redirectUrl->redirect->getTargetUrl')
        ->once()
        ->andReturn('https://linkedin.com/some-long-random-name');

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/redirect/linkedin?redirect_url=https://ai.syllaby.io/calendar')
        ->assertOk()
        ->assertJsonPath('data.redirect_url', 'https://linkedin.com/some-long-random-name');
});

it('throws error on invalid redirect url', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/redirect/linkedin?redirect_url=https://malicious-url.com')
        ->assertBadRequest()
        ->assertJsonPath('message', __('social.invalid_url'));
});

it('throws error on empty redirect url', function () {
   $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
         ->getJson('v1/social/redirect/linkedin')
         ->assertBadRequest()
         ->assertJsonPath('message', __('social.invalid_url'));
});