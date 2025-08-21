<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUserSecurityAndTransactionalData implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user) {}

    public function handle(): void
    {
        DB::table('password_resets')->where('email', $this->user->email)->delete();

        $this->user->coupons()->detach();

        DB::table('user_feedback')->where('user_id', $this->user->id)->delete();

        DB::table('publication_aggregates')->whereIn('publication_id', function ($query) {
            $query->select('id')->from('publications')->where('user_id', $this->user->id);
        })->delete();
    }
}
