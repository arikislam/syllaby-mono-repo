<?php

namespace Tests\Feature\Users;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Auth\Notifications\PasswordUpdatedConfirmation;

uses(RefreshDatabase::class);

it('shows that user can update his password', function () {
    Notification::fake();

    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('v1/user/password', [
        'current_password' => '12345678', // default password from factory
        'password' => TestCase::NEW_PASSWORD,
        'password_confirmation' => TestCase::NEW_PASSWORD,
    ])->assertOk();

    expect($response->json('data'))->toHaveKeys([
        'id',
        'name',
        'email',
        'settings',
        'notifications',
        'credits',
    ]);

    $user->refresh();

    $this->assertTrue(Hash::check(TestCase::NEW_PASSWORD, $user->password));
});

it('shows that email is dispatched after user updated his password', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->putJson('v1/user/password', [
            'current_password' => '12345678', // default password from factory
            'password' => TestCase::NEW_PASSWORD,
            'password_confirmation' => TestCase::NEW_PASSWORD,
        ])->assertOk();

    expect($response->json('data'))->toHaveKeys([
        'id',
        'name',
        'email',
        'settings',
        'notifications',
        'credits',
    ]);

    $user->refresh();

    $this->assertTrue(Hash::check(TestCase::NEW_PASSWORD, $user->password));

    Notification::assertSentTo($user, PasswordUpdatedConfirmation::class);
});
