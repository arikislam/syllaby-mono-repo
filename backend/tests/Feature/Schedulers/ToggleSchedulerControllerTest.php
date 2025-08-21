<?php

namespace Tests\Feature\Schedulers;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Schedulers\Scheduler;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can toggle a scheduler from running to paused', function () {
    Carbon::setTestNow('2024-01-10');

    $user = User::factory()->create();
    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::PUBLISHING,
    ]);

    $this->actingAs($user);
    $response = $this->putJson("/v1/schedulers/{$scheduler->id}/toggle");

    $response->assertOk();
    expect($response->json('data'))
        ->id->toBe($scheduler->id)
        ->status->toBe(SchedulerStatus::PAUSED->value)
        ->paused_at->not->toBeNull();

    $scheduler->refresh();
    expect($scheduler->isPaused())->toBeTrue();
});

it('can toggle a scheduler from paused to active', function () {
    Carbon::setTestNow('2024-01-10');

    $user = User::factory()->create();
    $scheduler = Scheduler::factory()->for($user)->create([
        'paused_at' => now(),
        'status' => SchedulerStatus::PAUSED,
    ]);

    $this->actingAs($user);
    $response = $this->putJson("/v1/schedulers/{$scheduler->id}/toggle");

    $response->assertOk();
    expect($response->json('data'))
        ->id->toBe($scheduler->id)
        ->status->toBe(SchedulerStatus::PUBLISHING->value)
        ->paused_at->toBeNull();

    $scheduler->refresh();
    expect($scheduler->isPaused())->toBeFalse();
});

it('updates associated events when toggling', function () {
    Carbon::setTestNow('2024-01-10');

    $user = User::factory()->create();
    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::PUBLISHING,
    ]);

    $futureEvent = $scheduler->events()->create([
        'user_id' => $user->id,
        'starts_at' => now()->addDay(),
    ]);
    $pastEvent = $scheduler->events()->create([
        'user_id' => $user->id,
        'starts_at' => now()->subDay(),
    ]);

    $this->actingAs($user);
    $response = $this->putJson("/v1/schedulers/{$scheduler->id}/toggle");

    $response->assertOk();
    expect($response->json('data'))
        ->id->toBe($scheduler->id)
        ->status->toBe(SchedulerStatus::PAUSED->value)
        ->paused_at->not->toBeNull();

    $futureEvent->refresh();
    $pastEvent->refresh();

    expect($futureEvent->cancelled_at)->not->toBeNull();
    expect($pastEvent->cancelled_at)->toBeNull();
});

it('fails to toggle a scheduler that is not completed', function () {
    $user = User::factory()->create();
    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::GENERATING,
    ]);

    $this->actingAs($user);
    $response = $this->putJson("/v1/schedulers/{$scheduler->id}/toggle");

    $response->assertStatus(403);
});
