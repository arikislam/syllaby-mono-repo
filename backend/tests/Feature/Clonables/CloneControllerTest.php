<?php

namespace Tests\Feature\Clonables;

use App\Syllaby\Users\User;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Clonables\Enums\CloneStatus;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Sequence;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('display a list of pending voice clone intents', function () {
    $user = User::factory()->create();
    $john = User::factory()->create();

    Clonable::factory()->count(5)->state(new Sequence(
        ['user_id' => $user->id, 'model_type' => Voice::class, 'status' => CloneStatus::PENDING],
        ['user_id' => $user->id, 'model_type' => Voice::class, 'status' => CloneStatus::COMPLETED],
        ['user_id' => $user->id, 'model_type' => Avatar::class, 'status' => CloneStatus::COMPLETED],
        ['user_id' => $john->id, 'model_type' => Voice::class, 'status' => CloneStatus::PENDING],
        ['user_id' => $john->id, 'model_type' => Avatar::class, 'status' => CloneStatus::PENDING],
    ))->create(['purchase_id' => null]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/clones');

    $response->assertJsonCount(3, 'data');
});

it('display a single clone intent', function () {
    $user = User::factory()->create();

    $clone = Clonable::factory()->for($user)->create([
        'purchase_id' => null,
        'status' => CloneStatus::PENDING,
        'model_type' => (new Voice)->getMorphClass(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("/v1/clones/{$clone->id}");

    expect($response->json('data'))
        ->id->toBe($clone->id)
        ->user_id->toBe($user->id)
        ->model_type->toBe((new Voice)->getMorphClass());
});

it('fails display clones created by other users', function () {
    $user = User::factory()->create();

    $clone = Clonable::factory()->create([
        'purchase_id' => null,
        'status' => CloneStatus::PENDING,
        'model_type' => (new Voice)->getMorphClass(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("/v1/clones/{$clone->id}");

    $response->assertForbidden();
});

it('deletes a voice clone', function () {
    $user = User::factory()->create();

    $voice = Voice::factory()->create();
    $clone = Clonable::factory()->for($user)->create([
        'purchase_id' => null,
        'model_id' => $voice->id,
        'model_type' => $voice->getMorphClass(),
    ]);

    Http::fake([
        "https://api.elevenlabs.io/v1/voices/$voice->provider_id" => Http::response([]),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/clones/$clone->id");

    $response->assertNoContent();

    $this->assertDatabaseMissing('voices', ['id' => $voice->id]);
    $this->assertDatabaseMissing('clonables', ['id' => $clone->id]);
});

it('fails to delete another user voice clone', function () {
    $user = User::factory()->create();

    $voice = Voice::factory()->create();
    $clone = Clonable::factory()->create([
        'purchase_id' => null,
        'model_id' => $voice->id,
        'model_type' => $voice->getMorphClass(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/clones/$clone->id");

    $response->assertForbidden();
});
