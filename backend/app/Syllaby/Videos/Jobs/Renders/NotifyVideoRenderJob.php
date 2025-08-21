<?php

namespace App\Syllaby\Videos\Jobs\Renders;

use App\Syllaby\Videos\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Videos\Notifications\VideoFootageRendered;

class NotifyVideoRenderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Video $video) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->shouldNotify()) {
            return;
        }

        $this->video->load('user');
        $this->video->user->notify(new VideoFootageRendered($this->video));
    }

    /**
     * Check if the current real clone is completed.
     */
    private function shouldNotify(): bool
    {
        $completed = $this->video->status === VideoStatus::COMPLETED;

        return $completed && blank($this->video->scheduler_id);
    }
}
