<?php

namespace App\Syllaby\Animation\Jobs;

use Str;
use DateTime;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use App\Syllaby\Animation\Contracts\AnimationGenerator;
use App\Syllaby\Animation\Events\AnimationGenerationFailed;

class PollAnimationGeneration implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(protected Asset $asset, protected Faceless $faceless) {}

    /**
     * Handle the job.
     */
    public function handle(AnimationGenerator $animator): void
    {
        $response = $animator->status($this->asset->provider_id);

        match (Str::lower($response->status)) {
            'processing', 'queueing', 'preparing' => $this->release(60),
            'success' => dispatch(new DownloadAnimation($this->asset, $response->fileId, $this->faceless)),
            default => $this->fail(),
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        Log::alert('Poll - Animation Generation Failed', ['task_id' => $this->asset->provider_id]);

        event(new AnimationGenerationFailed($this->asset, $this->faceless));
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(15);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->asset->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["animation-polling:{$this->asset->id}"];
    }
}
