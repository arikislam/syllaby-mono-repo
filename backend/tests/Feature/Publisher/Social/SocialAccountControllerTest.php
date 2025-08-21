<?php

namespace Tests\Feature\Publisher\Social;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Event;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can fetch the social accounts of a user', function () {
    Event::fake();

    $this->withoutMiddleware(PaidCustomersMiddleware::class);

    $user = User::factory()->create();

    SocialAccount::factory()->for($user)
        ->has(SocialChannel::factory()->individual()->count(2), 'channels')
        ->youtube()
        ->createQuietly();

    SocialAccount::factory()->for($user)
        ->has(SocialChannel::factory()->organization()->count(2), 'channels')
        ->linkedin()
        ->createQuietly();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social-accounts')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(4);
});
