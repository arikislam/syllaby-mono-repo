<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Actions\DeletePublicationAction;

class DeleteUserPublications implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user) {}

    /**
     * Execute the job.
     */
    public function handle(DeletePublicationAction $remover): void
    {
        Publication::where('user_id', $this->user->id)->chunkById(50, function ($publications) use ($remover) {
            $publications->each(fn (Publication $publication) => $remover->handle($publication));
        });
    }
}
