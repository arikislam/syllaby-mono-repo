<?php

namespace Tests\Feature\Ideas;

use Illuminate\Support\Arr;
use App\Syllaby\Ideas\Idea;
use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\Enums\Networks;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('allows to browse ideas from a previously searched keyword', function () {
    $user = User::factory()->create();

    $keyword = Keyword::factory()->hasAttached($user)->create([
        'network' => Networks::GOOGLE->value,
    ]);

    Idea::factory()->for($keyword)->count(4)->create();

    $query = Arr::query([
        'filter' => [
            'keyword' => $keyword->slug,
            'network' => Networks::GOOGLE->value,
        ],
        'per_page' => 2,
    ]);

    $this->actingAs($user);
    $response = $this->getJson('/v1/ideas?' . $query);
    expect($response->json('data'))
        ->toHaveCount(2)
        ->and($response->json('links'))
        ->next->not->toBeNull();

    $response = $this->getJson($response->json('links.next'));
    expect($response->json('data'))
        ->toHaveCount(2)
        ->and($response->json('links'))
        ->next->toBeNull();
});

it('fails to browse ideas from a non previously searched keyword', function () {
    $user = User::factory()->create();

    $keyword = Keyword::factory()->create([
        'network' => Networks::GOOGLE->value,
    ]);

    Idea::factory()->for($keyword)->count(4)->create();

    $query = Arr::query([
        'filter' => [
            'keyword' => $keyword->slug,
            'network' => Networks::GOOGLE->value,
        ],
        'per_page' => 2,
    ]);

    $this->actingAs($user);
    $response = $this->getJson('/v1/ideas?' . $query);
    $response->assertForbidden();
});
