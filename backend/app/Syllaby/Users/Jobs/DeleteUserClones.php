<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Clonables\Actions\DeleteClonableAction;

class DeleteUserClones implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user) {}

    /**
     * Execute the job.
     */
    public function handle(DeleteClonableAction $remover): void
    {
        Clonable::where('user_id', $this->user->id)->get()->each(
            fn (Clonable $clonable) => $remover->handle($clonable)
        );
    }
}
