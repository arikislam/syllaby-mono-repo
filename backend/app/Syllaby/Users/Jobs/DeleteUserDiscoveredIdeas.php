<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUserDiscoveredIdeas implements ShouldQueue
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
        $this->user->keywords()->detach();
    }
}
