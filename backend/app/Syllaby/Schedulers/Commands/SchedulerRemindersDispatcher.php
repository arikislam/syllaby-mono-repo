<?php

namespace App\Syllaby\Schedulers\Commands;

use App\Syllaby\Planner\Event;
use Illuminate\Console\Command;
use App\Shared\Reminders\Reminder;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Notifications\SchedulerReminderNotification;

class SchedulerRemindersDispatcher extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'syllaby:scheduler-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Process and dispatch scheduled events reminders';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = now();
        $window = $now->copy()->addMinute();

        if (! $identifiers = Reminder::get(Scheduler::REMINDER_KEY, $now, $window)) {
            return;
        }

        Event::whereIn('id', $identifiers)->with(['user', 'model.video'])->get()->each(function ($event) {
            $event->user->notify(new SchedulerReminderNotification($event, $event->model, $event->model->video));
        });

        Reminder::clear(Scheduler::REMINDER_KEY, $now, $window);
    }
}
