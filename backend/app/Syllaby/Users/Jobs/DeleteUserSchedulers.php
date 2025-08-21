<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUserSchedulers implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Use Lazy deletion or chunking to prevent memory issues
        Occurrence::with('generator')->where('user_id', $this->user->id)->get()->each(function ($occurrence) {
            $occurrence->generator?->delete();
            $occurrence->delete();
        });

        Scheduler::with('generator')->where('user_id', $this->user->id)->get()->each(function (Scheduler $scheduler) {
            $scheduler->generator?->delete();
            $scheduler->delete();
        });
    }
}
