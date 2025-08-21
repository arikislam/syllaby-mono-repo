<?php

namespace App\Syllaby\RealClones\Jobs;

use Illuminate\Bus\Queueable;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\RealClones\Notifications\RealCloneGenerated;

class NotifyRealCloneGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * Create a new job instance.
     */
    public function __construct(protected RealClone $clone)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->completed()) {
            return;
        }

        $this->clone->load('user');
        $this->clone->user->notify(new RealCloneGenerated($this->clone));
    }

    /**
     * Check if the current real clone is completed.
     */
    private function completed(): bool
    {
        return $this->clone->status === RealCloneStatus::COMPLETED;
    }
}
