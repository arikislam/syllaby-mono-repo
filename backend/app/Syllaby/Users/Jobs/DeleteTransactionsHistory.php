<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use App\Syllaby\Credits\CreditHistory;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteTransactionsHistory implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user) {}

    public function handle(): void
    {
        CreditHistory::where('user_id', $this->user->id)->delete();
    }
}
