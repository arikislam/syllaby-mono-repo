<?php

namespace Tests\Feature\Auth;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\TestRegisterUserJob;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(['https://api.pwnedpasswords.com/range/E5BB2' => Http::response([])]);
});

it('shows successful registration', function () {
    Mail::fake();

    /** @var User $user */
    $user = User::factory()->make(['email' => 'sharryy@syllaby.io']);

    $response = $this->postJson('v1/authentication/register', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'SomethingSuperSecret12!@',
    ]);

    $response->assertCreated();

    $response->assertJson([
        'data' => [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ],
    ]);

    $this->assertDatabaseHas(User::class, $user->only('email'));
});

it('shows user cant register with duplicate email address', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'sharryy@syllaby.io']);

    $this->postJson('v1/authentication/register', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'SomethingSuperSecret12!@',
        'password_confirmation' => 'SomethingSuperSecret12!@',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJson([
        'errors' => [
            'email' => [
                'The email has already been taken.',
            ],
        ],
    ]);
});

it('shows user cant register with duplicate email address due to race condition', function () {
    Mail::fake();

    $this->expectException(ValidationException::class);

    $email = 'lorem@syllaby.io';
    $data = [
        'name' => 'Test User',
        'email' => $email,
        'password' => 'SomethingSuperSecret12!@',
        'password_confirmation' => 'SomethingSuperSecret12!@',
    ];

    dispatch(new TestRegisterUserJob($data));
    dispatch(new TestRegisterUserJob($data));
    dispatch(new TestRegisterUserJob($data));

    $this->assertEquals(1, User::whereEmail($email)->count());
});

it('shows successful registration with mocked access token', function () {
    Mail::fake();

    $user = User::factory()->make(['email' => 'sharryy@syllaby.io']);

    $response = $this->postJson('v1/authentication/register', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'SomethingSuperSecret12!@',
        'password_confirmation' => 'SomethingSuperSecret12!@',
    ]);

    $response->assertCreated();

    $response->assertJson([
        'data' => [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ],
    ]);

    $this->assertNotNull($response->json('data.token'));

    $this->assertDatabaseHas(User::class, $user->only('email'));
});

it('shows successful registration with opt for mailing list', function () {
    Mail::fake();

    /** @var User $user */
    $user = User::factory()->make(['email' => 'sharryy@syllaby.io']);

    $response = $this->postJson('v1/authentication/register', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'SomethingSuperSecret12!@',
        'password_confirmation' => 'SomethingSuperSecret12!@',
        'newsletter' => true,
    ]);

    $response->assertCreated();

    $response->assertJson([
        'data' => [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'settings' => [
                    'mailing_list' => true,
                ],
            ],
        ],
    ]);

    $this->assertDatabaseHas(User::class, $user->only('email'));
});

it('allows users to register with a stripe promo code', function () {
    Mail::fake();

    $user = User::factory()->make(['email' => 'sharryy@syllaby.io']);

    $response = $this->postJson('v1/authentication/register', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'SomethingSuperSecret12!@',
        'password_confirmation' => 'SomethingSuperSecret12!@',
        'promo_code' => 'promo_foobar',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('users', [
        'id' => $response->json('data.user.id'),
        'promo_code' => 'promo_foobar',
    ]);
});

it('allows users to register with a promo code and exempt payment method', function () {
    Mail::fake();

    config(['syllaby.campaigns' => 'promo_foobar,no-pm-trial']);
    $user = User::factory()->make(['email' => 'sharryy@syllaby.io']);

    $response = $this->postJson('v1/authentication/register', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'SomethingSuperSecret12!@',
        'password_confirmation' => 'SomethingSuperSecret12!@',
        'promo_code' => 'no-pm-trial',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('users', [
        'id' => $response->json('data.user.id'),
        'promo_code' => null,
        'pm_exemption_code' => 'no-pm-trial',
    ]);
});

it('creates a suppressed email record when using a disposable email address', function () {
    Mail::fake();

    $user = User::factory()->make(['email' => 'sharryy@mailinator.com']);

    $response = $this->postJson('v1/authentication/register', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'SomethingSuperSecret12!@',
        'password_confirmation' => 'SomethingSuperSecret12!@',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('users', [
        'id' => $response->json('data.user.id'),
        'email' => $user->email,
    ]);

    $this->assertDatabaseHas('suppressions', [
        'email' => $user->email,
        'reason' => 'Disposable Email',
        'bounce_type' => 'Permanent',
    ]);
});
