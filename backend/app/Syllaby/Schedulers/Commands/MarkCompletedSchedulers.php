<?php

namespace App\Syllaby\Schedulers\Commands;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;

class MarkCompletedSchedulers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'syllaby:complete-schedulers';

    /**
     * The console command description.
     */
    protected $description = 'Mark schedulers as completed or publishing';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Scheduler::query()
            ->where('status', SchedulerStatus::PUBLISHING)
            ->whereIn('id', $this->matchEvents())
            ->update(['status' => SchedulerStatus::COMPLETED]);

        Scheduler::query()
            ->where('status', SchedulerStatus::SCHEDULED)
            ->chunkById(100, fn (Collection $scheduler) => $this->updateStatus($scheduler));
    }

    /**
     * Fetches the ids of the schedulers that should be marked as completed.
     */
    private function matchEvents(): Closure
    {
        return fn ($query) => $query->select('scheduler_id')
            ->from('events')
            ->whereColumn('scheduler_id', 'schedulers.id')
            ->groupBy('scheduler_id')
            ->havingRaw('MAX(starts_at) < ?', [now()]);
    }

    /**
     * Updates the status of the scheduler to publishing if the first recurrence date is in the past.
     */
    public function updateStatus(Collection $schedulers): void
    {
        foreach ($schedulers as $scheduler) {
            $date = Arr::first($scheduler->rdates());

            if ($date && $date->isPast()) {
                $scheduler->update(['status' => SchedulerStatus::PUBLISHING]);
            }
        }
    }
}
