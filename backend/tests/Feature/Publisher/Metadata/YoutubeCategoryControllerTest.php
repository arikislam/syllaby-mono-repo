<?php

namespace Tests\Feature\Publisher\Metadata;

use App\Syllaby\Users\User;
use App\Syllaby\Metadata\Metadata;
use Illuminate\Support\Facades\Http;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can fetch categories for Youtube', function () {
    Http::fake();

    /** @var User $user */
    $user = User::factory()->create();

    Metadata::factory()->categories()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/metadata/youtube/categories')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'title',
                ],
            ],
        ]);
});
