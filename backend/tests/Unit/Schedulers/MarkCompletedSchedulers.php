<?php

namespace Tests\Unit\Schedulers;

use Carbon\Carbon;
use App\Syllaby\Planner\Event;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Support\Facades\Queue;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Carbon::setTestNow('2024-01-01 10:00:00');
});

it('marks schedulers as completed when all events are in the past', function () {
    $scheduler = Scheduler::factory()->create([
        'status' => SchedulerStatus::PUBLISHING,
    ]);

    Event::factory()->create([
        'scheduler_id' => $scheduler->id,
        'starts_at' => now()->subHour(),
    ]);

    $this->artisan('syllaby:complete-schedulers');

    expect($scheduler->fresh()->status)->toBe(SchedulerStatus::COMPLETED);
});

it('does not mark schedulers as completed when events are in the future', function () {
    $scheduler = Scheduler::factory()->create([
        'status' => SchedulerStatus::PUBLISHING,
    ]);

    Event::factory()->create([
        'scheduler_id' => $scheduler->id,
        'starts_at' => now()->addHour(),
    ]);

    $this->artisan('syllaby:complete-schedulers');

    expect($scheduler->fresh()->status)->toBe(SchedulerStatus::PUBLISHING);
});

it('does not mark schedulers as completed when latest event is in the past but has future events', function () {
    $scheduler = Scheduler::factory()->create([
        'status' => SchedulerStatus::PUBLISHING,
    ]);

    // Create past event
    Event::factory()->create([
        'scheduler_id' => $scheduler->id,
        'starts_at' => now()->subHours(2),
    ]);

    // Create future event
    Event::factory()->create([
        'scheduler_id' => $scheduler->id,
        'starts_at' => now()->addHour(),
    ]);

    $this->artisan('syllaby:complete-schedulers');

    expect($scheduler->fresh()->status)->toBe(SchedulerStatus::PUBLISHING);
});

it('only processes publishing schedulers', function () {
    $completedScheduler = Scheduler::factory()->create([
        'status' => SchedulerStatus::COMPLETED,
    ]);

    Event::factory()->create([
        'scheduler_id' => $completedScheduler->id,
        'starts_at' => now()->subHour(),
    ]);

    $this->artisan('syllaby:complete-schedulers');

    expect($completedScheduler->fresh()->status)->toBe(SchedulerStatus::COMPLETED);
});
