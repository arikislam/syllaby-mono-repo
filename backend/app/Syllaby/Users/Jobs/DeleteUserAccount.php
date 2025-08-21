<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUserAccount implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user) {}

    public function handle(): void
    {
        $this->user->forceDelete();
    }
}
