<?php

namespace Tests\Feature\RealClones;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\RealClones\RealClone;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can display the status of  real clone', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create([
        'status' => RealCloneStatus::GENERATING,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("v1/real-clones/$clone->id/status");

    $response->assertOk();

    expect($response->json('data'))
        ->id->toBe($clone->id)
        ->status->toBe(RealCloneStatus::GENERATING->value);
});
