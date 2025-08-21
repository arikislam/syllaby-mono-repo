<?php

namespace Tests\Feature\Auth;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Requests\Authentication\LoginRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows successful login', function () {
    $user = User::factory()->create();

    $response = $this->postJson('v1/authentication/login', [
        'email' => $user->email,
        'password' => '12345678',
    ]);

    $response->assertOk();

    $response->assertJson([
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,

                'credits' => [
                    'remaining' => $user->remaining_credit_amount,
                    'total' => $user->monthly_credit_amount,
                    'extra' => $user->extra_credits,
                ],

                'settings' => $user->settings,
                'notifications' => $user->notifications,
            ],
        ],
    ]);

    $this->assertAuthenticatedAs($user, 'sanctum');
});

it('shows successful login with mocked token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('v1/authentication/login', [
        'email' => $user->email,
        'password' => '12345678',
    ]);

    $response->assertOk();

    $response->assertJson([
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,

                'credits' => [
                    'remaining' => $user->remaining_credit_amount,
                    'total' => $user->monthly_credit_amount,
                    'extra' => $user->extra_credits,
                ],

                'settings' => $user->settings,
                'notifications' => $user->notifications,
            ],
        ],
    ]);

    $this->assertNotNull($response->json('data.token'));

    $this->assertAuthenticatedAs($user, 'sanctum');
});

it('shows failed login with wrong credentials', function () {
    $user = User::factory()->make();

    $this->postJson('v1/authentication/login', [
        'email' => $user->email,
        'password' => '12345678',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('auth.failed')]);
});

it('shows failed login with inactive user', function () {
    $user = User::factory()->create([
        'active' => false,
    ]);

    $this->postJson('v1/authentication/login', [
        'email' => $user->email,
        'password' => '12345678',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('auth.failed')]);
});

it('can throttle spam login attempts', function () {
    $user = User::factory()->create();

    $throttleKey = LoginRequest::LOGIN_THROTTLE_PREFIX.$user->email;

    RateLimiter::shouldReceive('tooManyAttempts')
        ->once()
        ->with($throttleKey, 5)
        ->andReturnTrue();

    RateLimiter::shouldReceive('availableIn')
        ->once()
        ->with($throttleKey)
        ->andReturn(30);

    $this->postJson('v1/authentication/login', [
        'email' => $user->email,
        'password' => '12345678',
    ])
        ->assertTooManyRequests()
        ->assertJsonValidationErrors(['email' => __('auth.throttle', ['seconds' => 30])]);
});
