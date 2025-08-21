<?php

namespace Tests\Feature\RealClones;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\RealClones\RealClone;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\RealClones\Jobs\CreateRealCloneMediaJob;
use App\Syllaby\RealClones\Jobs\NotifyRealCloneGenerationJob;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can retry syncing a real clone', function () {
    Bus::fake();

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create([
        'retries' => 0,
        'status' => RealCloneStatus::SYNC_FAILED,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/$clone->id/retries");

    $response->assertAccepted();

    Bus::assertChained([
        CreateRealCloneMediaJob::class,
        NotifyRealCloneGenerationJob::class,
    ]);

    $clone = $clone->fresh();

    expect($response->json('data'))
        ->id->toBe($clone->id)
        ->status->toBe(RealCloneStatus::SYNCING->value)
        ->and($clone->retries)->toBe(1);
});

it('marks real clone as failed when max retries is reached', function () {
    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create([
        'retries' => 1,
        'status' => RealCloneStatus::SYNC_FAILED,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/$clone->id/retries");

    $response->assertOk();

    $clone = $clone->fresh();

    expect($response->json('data'))
        ->id->toBe($clone->id)
        ->status->toBe(RealCloneStatus::FAILED->value)
        ->and($clone->retries)->toBe(1);
});

it('fails to retry real clone that is not marked as failed', function () {
    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create([
        'retries' => 0,
        'status' => RealCloneStatus::FAILED,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/$clone->id/retries");

    $response->assertForbidden();

    $clone = $clone->fresh();

    expect($clone)->status->toBe(RealCloneStatus::FAILED);
});

it('fails to retry real clone that is not marked as completed', function () {
    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create([
        'retries' => 0,
        'status' => RealCloneStatus::COMPLETED,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/$clone->id/retries");

    $response->assertForbidden();

    $clone = $clone->fresh();

    expect($clone)->status->toBe(RealCloneStatus::COMPLETED);
});

it('fails to retry another user real clone', function () {
    $clone = RealClone::factory()->create([
        'retries' => 0,
        'status' => RealCloneStatus::SYNC_FAILED,
    ]);

    $this->actingAs(User::factory()->create(), 'sanctum');
    $response = $this->postJson("/v1/real-clones/$clone->id/retries");

    $response->assertForbidden();
});
