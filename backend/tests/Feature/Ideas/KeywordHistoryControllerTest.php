<?php

namespace Tests\Feature\Ideas;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Ideas\Idea;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\Enums\Networks;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('display for the authenticated user a simple keyword search history', function () {
    $user = User::factory()->create();

    Keyword::factory()->hasAttached($user)->create(['network' => Networks::YOUTUBE]);
    Keyword::factory()->hasAttached($user)->count(4)->create(['network' => Networks::GOOGLE]);

    $this->actingAs($user);

    $query = Arr::query([
        'per_page' => 2,
        'filter' => ['network' => Networks::GOOGLE->value],
    ]);
    $response = $this->getJson("/v1/keywords/history?$query");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2)
        ->not->toHaveProperty('metrics')
        ->and($response->json('links.next'))->not->toBeNull();

    $response = $this->getJson($response->json('links.next'));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2)
        ->not->toHaveProperty('metrics')
        ->and($response->json('links.next'))
        ->toBeNull();
});

it('display for the authenticated user a keyword search history with metrics', function () {
    $user = User::factory()->create();

    $ideas = Idea::factory()->count(4);
    Keyword::factory()->count(4)->hasAttached($user)->has($ideas)->create([
        'network' => Networks::GOOGLE,
    ]);

    $this->actingAs($user);

    $query = Arr::query([
        'per_page' => 2,
        'metrics' => 'true',
        'filter' => ['network' => Networks::GOOGLE->value],
    ]);
    $response = $this->getJson("/v1/keywords/history?$query");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2)
        ->toHaveKey('0.metrics')->toHaveKey('1.metrics')
        ->and($response->json('links.next'))->not->toBeNull();
});

it('fails to show other user keyword search history', function () {
    $john = User::factory()->create();
    $user = User::factory()->create();

    Keyword::factory()->hasAttached($user)->count(4)->create(['network' => Networks::GOOGLE]);

    $query = Arr::query([
        'per_page' => 2,
        'filter' => ['network' => Networks::GOOGLE->value],
    ]);
    $this->actingAs($john);
    $response = $this->getJson("/v1/keywords/history?$query");

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty()
        ->and($response->json('links.next'))->toBeNull();
});

it('allows users to delete keywords from their search history', function () {
    $user = User::factory()->create();

    $keyword = Keyword::factory()->hasAttached($user)->create();
    $idea = Idea::factory()->for($keyword)->create();

    $this->actingAs($user);
    $response = $this->deleteJson("/v1/keywords/$keyword->id/history");
    $response->assertNoContent();

    $this->assertDatabaseHas('keywords', ['id' => $keyword->id]);
    $this->assertDatabaseHas('ideas', ['id' => $idea->id, 'keyword_id' => $keyword->id]);
    $this->assertDatabaseMissing('keyword_user', ['user_id' => $user->id, 'keyword_id' => $keyword->id]);
});

it('fails to delete another user keyword search history', function () {
    $john = User::factory()->create();
    $user = User::factory()->create();

    $keyword = Keyword::factory()->hasAttached($john)->create();
    $idea = Idea::factory()->for($keyword)->create();

    $this->actingAs($user);
    $response = $this->deleteJson("/v1/keywords/$keyword->id/history");
    $response->assertForbidden();

    $this->assertDatabaseHas('keywords', ['id' => $keyword->id]);
    $this->assertDatabaseHas('ideas', ['id' => $idea->id, 'keyword_id' => $keyword->id]);
    $this->assertDatabaseHas('keyword_user', ['user_id' => $john->id, 'keyword_id' => $keyword->id]);
});
