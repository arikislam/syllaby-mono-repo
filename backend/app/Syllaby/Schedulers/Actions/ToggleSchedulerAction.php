<?php

namespace App\Syllaby\Schedulers\Actions;

use Illuminate\Support\Facades\DB;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;

class ToggleSchedulerAction
{
    /**
     * Toggle the scheduler and its associated events.
     */
    public function handle(Scheduler $scheduler): Scheduler
    {
        return DB::transaction(function () use ($scheduler) {
            $now = now();
            $toggle = $scheduler->isPaused() ? null : $now;

            $scheduler->events()->where('starts_at', '>=', $now)->update([
                'cancelled_at' => $toggle,
            ]);

            return tap($scheduler)->update([
                'paused_at' => $toggle,
                'status' => $this->status($scheduler),
            ]);
        });
    }

    private function status(Scheduler $scheduler): SchedulerStatus
    {
        return $scheduler->isPaused() ? SchedulerStatus::PUBLISHING : SchedulerStatus::PAUSED;
    }
}
