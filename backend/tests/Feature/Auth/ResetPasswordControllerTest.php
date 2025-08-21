<?php

namespace Tests\Feature\Auth;

use Mockery;
use Tests\TestCase;
use Mockery\MockInterface;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(['https://api.pwnedpasswords.com/range/E5BB2' => Http::response([])]);
});

test('unsuccessful password reset', function () {
    $user = User::factory()->create();

    $this->postJson('v1/recovery/reset', [
        'email' => $user->email,
        'token' => TestCase::FORGET_PASSWORD_TOKEN,
        'password' => TestCase::NEW_PASSWORD,
        'password_confirmation' => TestCase::NEW_PASSWORD,
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'email' => __('passwords.token'),
    ]);
});

test('successful password reset', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->mock(PasswordBroker::class, function (MockInterface $mock) use ($user) {
        $mock->shouldReceive('reset')
            ->once()
            ->withArgs(function (array $validatedData, callable $callback) use ($user) {
                $this->assertEquals([
                    'token' => TestCase::FORGET_PASSWORD_TOKEN,
                    'email' => $user->email,
                    'password' => TestCase::NEW_PASSWORD,
                ], $validatedData);

                $callback($user, TestCase::NEW_PASSWORD);

                return true;
            })->andReturn('passwords.reset');
    });

    $this->postJson('v1/recovery/reset', [
        'email' => $user->email,
        'token' => TestCase::FORGET_PASSWORD_TOKEN,
        'password' => TestCase::NEW_PASSWORD,
    ])->assertOk()->assertJson([
        'data' => [
            'message' => __('passwords.reset'),
        ],
    ]);

    $user->refresh();

    $this->assertTrue(Hash::check(TestCase::NEW_PASSWORD, $user->password));
});

afterEach(function () {
    Mockery::close();
});
