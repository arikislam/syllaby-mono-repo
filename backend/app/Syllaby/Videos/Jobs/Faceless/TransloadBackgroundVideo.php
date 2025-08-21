<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use App\Syllaby\Assets\Asset;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;

class TransloadBackgroundVideo implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Faceless $faceless, protected Asset $asset)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->faceless->addMediaFromUrl($this->asset->getFirstMediaUrl())
            ->preservingOriginal()
            ->toMediaCollection('background');
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        event(new FacelessGenerationFailed($this->faceless));
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->faceless->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["faceless-video-transload:{$this->faceless->id}"];
    }
}
