<?php

namespace Tests\Feature\Videos;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Footage;
use Tests\Stubs\CreatomateStub;
use App\Syllaby\Metadata\Timeline;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('display all the timeline elements of a given footage', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $footage = Footage::factory()->recycle($user)->create();
    Timeline::factory()->for($footage, 'model')->create([
        'user_id' => $user->id,
        'content' => CreatomateStub::timeline(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("v1/videos/footage/{$footage->id}/timeline");

    expect($response->json('data'))->toHaveCount(3);
});

it('can perform a bulk update to footage timeline', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $footage = Footage::factory()->recycle($user)->create();
    $timeline = Timeline::factory()->for($footage, 'model')->create([
        'user_id' => $user->id,
        'content' => CreatomateStub::timeline(),
    ]);

    $edits = $timeline->content;
    $elements = Arr::map($edits['elements'], function (mixed $element) {
        return ($element['type'] !== 'text') ? $element : [...$element, 'text' => 'Hello World AGAIN!'];
    });

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/videos/footage/{$footage->id}/timeline", [
        'elements' => json_encode($elements),
    ]);

    $response->assertOk();
    $response->assertJsonFragment([
        'text' => 'Hello World AGAIN!',
    ]);
});

it('fails to perform a bulk update on another users footage timeline', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $footage = Footage::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/videos/footage/{$footage->id}/timeline");

    $response->assertForbidden();
});

it('allows for an empty timeline', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $footage = Footage::factory()->recycle($user)->create();
    Timeline::factory()->for($footage, 'model')->create([
        'user_id' => $user->id,
        'content' => CreatomateStub::timeline(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/videos/footage/{$footage->id}/timeline", [
        'elements' => json_encode([]),
    ]);

    $response->assertOk();
    $response->assertJsonFragment([
        'data' => [],
    ]);
});
