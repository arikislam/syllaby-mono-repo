<?php

namespace Tests\Feature\Videos;

use App\Syllaby\Users\User;
use App\Syllaby\Characters\Genre;
use Illuminate\Support\Facades\Storage;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('display a list of available story genres for faceless videos', function () {
    Storage::fake('assets');

    $user = User::factory()->create();

    Genre::factory(2)->active()->create();

    $this->actingAs($user);
    $response = $this->getJson('/v1/videos/faceless/options/genres');

    $response->assertOk();
    $response->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['name', 'slug', 'consistent_character', 'preview'],
            ],
        ]);
});

it('display a list of available font presets for faceless videos', function () {
    Storage::fake('assets');

    $user = User::factory()->create();

    $this->actingAs($user);
    $response = $this->getJson('/v1/videos/faceless/options/fonts');

    $response->assertOk();
    $response->assertJsonCount(count(FontPresets::values()), 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['name', 'slug', 'font_family', 'preview'],
            ],
        ]);
});
