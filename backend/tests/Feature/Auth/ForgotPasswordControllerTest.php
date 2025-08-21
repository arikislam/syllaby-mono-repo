<?php

namespace Tests\Feature\Auth;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Notification;
use App\Syllaby\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('reset password mail is sent', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson('v1/recovery/forgot', [
        'email' => $user->email,
    ]);

    $response->assertOk();

    $response->assertJson([
        'data' => [
            'message' => __('passwords.sent'),
        ],
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('mail is sent and reset password link is properly generated', function () {
    Notification::fake();

    config(['app.frontend_url' => 'https://testing.syllaby.dev']);

    $user = User::factory()->create();

    $response = $this->postJson('v1/recovery/forgot', [
        'email' => $user->email,
    ]);

    $response->assertOk();

    $response->assertJson([
        'data' => [
            'message' => __('passwords.sent'),
        ],
    ]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $token = urlencode($notification->token);
        $email = urlencode($user->getEmailForPasswordReset());

        $this->assertTrue($notification->user->is($user));
        $this->assertEquals(config('app.frontend_url')."/reset-password?token={$token}&email={$email}", $notification->url);

        return true;
    });
});

test('only registered users can use forgot password feature', function () {
    Notification::fake();

    /** @var User $user */
    $user = User::factory()->make();

    $response = $this->postJson('v1/recovery/forgot', [
        'email' => $user->email,
    ]);

    $response->assertUnprocessable();

    $response->assertJson([
        'message' => __('passwords.user'),
    ]);

    Notification::assertNothingSent();
});
