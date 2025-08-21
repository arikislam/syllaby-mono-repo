<?php

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Footage;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

test('user must be authenticated to save a footage preference', function () {
    $footage = Footage::factory()->create();

    $this->putJson("/v1/videos/footage/{$footage->id}/preference", [
        'preference' => [
            'aspect_ratio' => '16:9',
        ],
    ])->assertUnauthorized();
});

it('can save a footage preference', function () {
    $user = User::factory()->create();

    $footage = Footage::factory()->for($user)->create();

    $this->actingAs($user)->putJson("/v1/videos/footage/{$footage->id}/preference", [
        'preference' => [
            'aspect_ratio' => '16:9',
        ],
    ])->assertOk()->assertJsonFragment(['aspect_ratio' => '16:9']);

    expect($footage->refresh()->preference)->toBe(['aspect_ratio' => '16:9']);
});

it('cant save a footage preference for another user', function () {
    $user = User::factory()->create();

    $footage = Footage::factory()->for($user)->create();

    $this->actingAs(User::factory()->create())->putJson("/v1/videos/footage/{$footage->id}/preference", [
        'preference' => [
            'aspect_ratio' => '16:9',
        ],
    ])->assertForbidden();
});

test('preference must have an aspect ratio', function () {
    $user = User::factory()->create();

    $footage = Footage::factory()->for($user)->create();

    $this->actingAs($user)->putJson("/v1/videos/footage/{$footage->id}/preference", [
        'preference' => [],
    ])->assertJsonValidationErrors('preference.aspect_ratio');
});

it('can update a footage preference', function () {
    $user = User::factory()->create();

    $footage = Footage::factory()->for($user)->create(['preference' => ['aspect_ratio' => '4:3']]);

    $this->actingAs($user)->putJson("/v1/videos/footage/{$footage->id}/preference", [
        'preference' => [
            'aspect_ratio' => '16:9',
        ],
    ])->assertOk()->assertJsonFragment(['aspect_ratio' => '16:9']);

    expect($footage->refresh()->preference)->toBe(['aspect_ratio' => '16:9']);
});