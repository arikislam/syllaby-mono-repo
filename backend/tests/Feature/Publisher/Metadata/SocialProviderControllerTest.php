<?php

namespace Tests\Feature\Publisher\Metadata;

use App\Syllaby\Users\User;
use App\Http\Middleware\PaidCustomersMiddleware;

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('returns a list of social providers', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->getJson('/v1/metadata/social-providers')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'title',
                    'type',
                    'icon',
                ],
            ],
        ]);
});
