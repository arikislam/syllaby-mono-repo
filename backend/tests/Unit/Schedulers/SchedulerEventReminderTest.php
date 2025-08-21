<?php

namespace Tests\Unit\Schedulers;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Planner\Event;
use App\Shared\Reminders\Reminder;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Syllaby\Schedulers\Notifications\SchedulerReminderNotification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Redis::flushall();
    Notification::fake();
    Carbon::setTestNow('2024-01-01 10:00:00');
});

it('sends reminder notification 4 hours before event starts', function () {
    $user = User::factory()->create();
    $video = Video::factory()->create();

    $publication = Publication::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
    ]);

    $event = Event::factory()->create([
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'starts_at' => now()->addHours(4),
        'model_type' => Relation::getMorphAlias(Publication::class),
    ]);

    Reminder::set(Scheduler::REMINDER_KEY, now(), $event->id);

    $this->artisan('syllaby:scheduler-reminders');

    Notification::assertSentTo($user, SchedulerReminderNotification::class);
});

it('does not send reminder notification if not within the time window', function () {
    $user = User::factory()->create();
    $video = Video::factory()->create();

    $publication = Publication::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
    ]);

    $event = Event::factory()->create([
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => Publication::class,
        'starts_at' => now()->addHours(5),
    ]);

    Reminder::set(Scheduler::REMINDER_KEY, now()->addHour(), $event->id);

    $this->artisan('syllaby:scheduler-reminders');

    Notification::assertNothingSent();
});

it('clears reminders after sending notifications', function () {
    $user = User::factory()->create();
    $video = Video::factory()->create();

    $publication = Publication::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
    ]);

    $event = Event::factory()->create([
        'user_id' => $user->id,
        'model_id' => $publication->id,
        'model_type' => Publication::class,
        'starts_at' => now()->addHours(4),
    ]);

    Reminder::set(Scheduler::REMINDER_KEY, now(), $event->id);

    $this->artisan('syllaby:scheduler-reminders');

    expect(Reminder::get(Scheduler::REMINDER_KEY, now(), now()->addMinute()))->toBeEmpty();
});
