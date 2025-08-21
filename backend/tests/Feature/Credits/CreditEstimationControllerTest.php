<?php

namespace Tests\Feature\Credits;

use App\Syllaby\Users\User;
use App\Http\Middleware\PaidCustomersMiddleware;

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can estimate credits for idea', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('v1/credits/estimate', [
        'type' => 'idea',
    ])->assertOk()->assertJson([
        'data' => [
            'credits' => 15,
        ],
    ]);
});

it('can estimate credits for script', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('v1/credits/estimate', [
        'type' => 'script',
    ])->assertOk()->assertJson([
        'data' => [
            'credits' => 1,
        ],
    ]);
});

it('can estimate credits for exporting a video from the editor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('v1/credits/estimate', [
        'type' => 'video',
        'provider' => 'creatomate',
    ])->assertOk()->assertJson([
        'data' => ['credits' => 3],
    ]);
});

it('can estimate credits for AI faceless video', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('v1/credits/estimate', [
        'type' => 'faceless',
        'provider' => 'creatomate',
        'script' => 'Some text here',
        'has_genre' => true,
    ])->assertOk()->assertJson([
        'data' => ['credits' => 13],
    ]);
});

it('can estimate credits for normal faceless video', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('v1/credits/estimate', [
        'type' => 'faceless',
        'provider' => 'creatomate',
        'script' => 'Some text here',
        'has_genre' => false,
    ])->assertOk()->assertJson([
        'data' => ['credits' => 13],
    ]);
});

it('can estimate credits for real clone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('v1/credits/estimate', [
        'type' => 'real_clone',
        'provider' => 'heygen',
    ])->assertOk()->assertJson([
        'data' => [
            'credits' => ['video' => 25, 'speech' => 7],
        ],
    ]);
});
