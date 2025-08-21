<?php

namespace Tests\Feature\Schedulers;

use RRule\RRule;
use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Schedulers\Jobs\ExpandSchedulerTopic;
use App\Syllaby\Schedulers\Jobs\BulkCreateOccurrences;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('allows users to generate scripts for a scheduler', function () {
    Bus::fake();
    Carbon::setTestNow(now());

    $user = User::factory()->create();

    $rrule = new RRule([
        'INTERVAL' => 1,
        'DTSTART' => now()->startOfDay(),
        'BYHOUR' => [11, 12],
        'FREQ' => RRule::DAILY,
        'UNTIL' => now()->addDays(2)->startOfDay(),
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::DRAFT,
        'rrules' => [$rrule->rfcString()],
    ]);

    $this->actingAs($user);
    $response = $this->postJson("v1/schedulers/{$scheduler->id}/occurrences", [
        'topic' => $scheduler->topic,
        'duration' => 60,
        'language' => 'english',
    ]);

    $response->assertAccepted();

    Bus::assertChained([
        ExpandSchedulerTopic::class,
        BulkCreateOccurrences::class,
        Bus::chainedBatch(fn (PendingBatch $batch) => $batch->jobs->count() === 4),
    ]);

    $this->assertDatabaseHas('schedulers', [
        'id' => $scheduler->id,
        'status' => SchedulerStatus::WRITING,
    ]);
});

it('prevents unauthenticated users from creating scheduler scripts', function () {
    $scheduler = Scheduler::factory()->create();
    $response = $this->postJson("v1/schedulers/{$scheduler->id}/occurrences", []);

    $response->assertUnauthorized();
});

it('allows users to update occurrence script', function () {
    Bus::fake();
    Carbon::setTestNow(now());

    $user = User::factory()->create();
    $occurrence = Occurrence::factory()->for($user)->create([
        'topic' => 'Hello',
        'script' => 'Foo Bar',
    ]);

    $this->actingAs($user);
    $response = $this->patchJson("v1/schedulers/occurrences/{$occurrence->id}", [
        'script' => 'Baz',
    ]);

    $response->assertOk();
    expect($response->json('data'))
        ->script->toBe('Baz')
        ->topic->toBe('Hello');
});

it('correctly calculates if user has enough credits to run the scheduler', function () {
    Bus::fake();
    Carbon::setTestNow(now());

    $user = User::factory()->create([
        'remaining_credit_amount' => 100,
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::DRAFT,
        'rrules' => [
            "DTSTART:20241002T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241004T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241020T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
        ],
    ]);

    $this->actingAs($user);
    $response = $this->postJson("v1/schedulers/{$scheduler->id}/occurrences", [
        'topic' => 'Test Topic',
        'duration' => 180,
        'language' => 'english',
    ]);

    $response->assertAccepted();

    Bus::assertDispatched(ExpandSchedulerTopic::class);

    $this->assertDatabaseHas('schedulers', [
        'id' => $scheduler->id,
        'status' => SchedulerStatus::WRITING,
    ]);
});

it('prevents creating scheduler occurrences when user has insufficient credits', function () {
    Bus::fake();
    Carbon::setTestNow(now());

    $user = User::factory()->create([
        'remaining_credit_amount' => 5,
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::DRAFT,
        'rrules' => [
            "DTSTART:20241002T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241004T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241020T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
        ],
    ]);

    $this->actingAs($user);
    $response = $this->postJson("v1/schedulers/{$scheduler->id}/occurrences", [
        'topic' => 'Test Topic',
        'duration' => 180,
        'language' => 'english',
    ]);

    $response->assertForbidden();

    Bus::assertNotDispatched(ExpandSchedulerTopic::class);

    $this->assertDatabaseHas('schedulers', [
        'id' => $scheduler->id,
        'status' => SchedulerStatus::DRAFT,
    ]);
});
