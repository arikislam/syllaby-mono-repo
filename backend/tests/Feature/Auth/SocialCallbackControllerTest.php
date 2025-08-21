<?php

namespace Tests\Feature\Auth;

use Mockery;
use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

uses(RefreshDatabase::class);

it('can receive a google account callback and create a new user', function () {
    Event::fake();

    Carbon::setTestNow('2021-01-01 00:00:00');

    $newUser = User::factory()->google()->make();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($newUser->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($newUser->name, 6))
        ->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($newUser->name);

    Socialite::shouldReceive('driver->stateless->user')->andReturn($abstractUser);

    $response = $this->getJson('v1/authentication/callback/google?code=123456')->assertOk();

    expect($response->json('data.user'))
        ->email->toBe($newUser->email)
        ->name->toBe($newUser->name);

    $this->assertDatabaseHas(User::class, [
        'email' => $newUser->email,
        'name' => $newUser->name,
        'provider_id' => '123456789',
        'provider' => SocialAccountEnum::Google->value,
        'mailing_list' => false,
        'email_verified_at' => Carbon::now(),
    ]);

    $user = User::where('email', $newUser->email)->first();

    $this->assertAuthenticatedAs($user, 'sanctum');

    Event::assertDispatched(Registered::class);
});

it('returns error message if email is already registered using some other method', function () {
    Event::fake();

    $user = User::factory()->google()->create();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($user->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($user->name, 6))
        ->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($user->name);

    Socialite::shouldReceive('driver->stateless->user')->andReturn($abstractUser);

    $this->getJson('v1/authentication/callback/google?code=123456')
        ->assertUnprocessable()
        ->assertSee(__('auth.already_registered'));
});

it('returns the user if it is already registered with same provider', function () {
    Event::fake();

    $user = User::factory()->google()->create();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser
        ->shouldReceive('getId')->andReturn($user->provider_id)
        ->shouldReceive('getEmail')->andReturn($user->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($user->name, 6))
        ->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($user->name);

    Socialite::shouldReceive('driver->stateless->user')->andReturn($abstractUser);

    $response = $this->getJson('v1/authentication/callback/google?code=123456')->assertOk();

    expect($response->json('data.user'))
        ->email->toBe($user->email)
        ->name->toBe($user->name);

    $this->assertDatabaseHas(User::class, [
        'email' => $user->email,
        'name' => $user->name,
        'provider_id' => $user->provider_id,
        'provider' => SocialAccountEnum::Google->value,
        'mailing_list' => $user->fresh()->mailing_list,
    ]);

    $this->assertAuthenticatedAs($user, 'sanctum');

    Event::assertDispatched(Login::class);
});
