<?php

namespace App\Syllaby\Schedulers\Commands;

use Illuminate\Console\Command;
use App\Shared\Reminders\Reminder;
use App\Syllaby\Schedulers\Scheduler;

class PruneReminderRecords extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'syllaby:prune-scheduler-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Deletes reminder records older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Reminder::prune(Scheduler::REMINDER_KEY);
    }
}
