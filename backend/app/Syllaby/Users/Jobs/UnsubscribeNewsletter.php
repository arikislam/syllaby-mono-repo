<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use App\Shared\Newsletters\Newsletter;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class UnsubscribeNewsletter implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user) {}

    public function handle(Newsletter $newsletter): void
    {
        $newsletter->unsubscribe($this->user);
    }
}
