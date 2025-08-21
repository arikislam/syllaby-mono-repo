<?php

namespace App\Syllaby\Schedulers\Jobs;

use App\System\Enums\QueueType;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use App\Syllaby\Schedulers\Notifications\SchedulerPublishingStarted;

class HandleSchedulerCompletion implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Scheduler $scheduler)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     **/
    public function handle(): void
    {
        $this->scheduler->update(['status' => SchedulerStatus::SCHEDULED]);

        $video = $this->scheduler->videos()->with('resource')->first();

        $this->scheduler->user->notify(new SchedulerPublishingStarted($this->scheduler, $video->resource));
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->scheduler->id;
    }
}
