<?php

namespace Tests\Feature\Clonables;

use App\Syllaby\Users\User;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Clonables\Enums\CloneStatus;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\RealClones\Enums\RealCloneProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('creates a avatar clone intent', function () {
    $user = User::factory()->create();

    $payload = [
        'terms' => true,
        'name' => 'Jane Doe',
        'gender' => 'female',
        'url' => 'https://example.com/video/foo.mp4',
        'provider' => RealCloneProvider::FASTVIDEO->value,
    ];

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/clones/avatars', $payload);

    $response->assertCreated();
    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->model_type->toBe((new Avatar)->getMorphClass())
        ->status->toBe(CloneStatus::PENDING->value)
        ->metadata->toMatchArray([
            'url' => $payload['url'],
            'name' => $payload['name'],
            'gender' => $payload['gender'],
        ]);
});

it('fails deleting another user avatar clone intent', function () {
    $user = User::factory()->create();

    $clonable = Clonable::factory()->create([
        'status' => CloneStatus::REVIEWING,
        'model_type' => (new Avatar)->getMorphClass(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/clones/{$clonable->id}");

    $response->assertForbidden();
});

it('fails deleting a avatar clone intent in review state', function () {
    $user = User::factory()->create();

    $clonable = Clonable::factory()->for($user)->create([
        'status' => CloneStatus::REVIEWING,
        'model_type' => (new Avatar)->getMorphClass(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/clones/{$clonable->id}");

    $response->assertForbidden();
});
