<?php

namespace App\Feature\Surveys;

use App\Syllaby\Users\User;
use App\Syllaby\Surveys\UserFeedback;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

test('it can submit feedback when unsubscribing', function () {
    $user = User::factory()->create();

    $input = UserFeedback::factory()->make(['user_id' => null]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/user-feedback', [
        'reason' => data_get($input, 'reason'),
        'details' => data_get($input, 'details'),
    ]);

    $response->assertCreated();

    $response->assertJsonFragment([
        'reason' => data_get($input, 'reason'),
        'details' => data_get($input, 'details'),
    ]);
});

test('it needs to provide a feedback reason', function () {
    $user = User::factory()->create();

    $input = UserFeedback::factory()->make([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/user-feedback');

    $response->assertUnprocessable();
});
