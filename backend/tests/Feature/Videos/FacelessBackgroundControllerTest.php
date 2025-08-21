<?php

namespace Tests\Feature\Videos;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Enums\AssetType;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can display list of asset background videos', function () {
    $user = User::factory()->create();

    Asset::factory()->global()->count(2)->withMedia()->create(['type' => AssetType::FACELESS_BACKGROUND]);

    $this->actingAs($user)->getJson('/v1/videos/faceless/options/backgrounds')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'type',
                    'slug',
                    'media',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
