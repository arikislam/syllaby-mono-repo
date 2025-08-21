<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUserFolders implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user) {}

    public function handle(): void
    {
        DB::table('resources')->where('user_id', $this->user->id)->update(['parent_id' => null]);

        DB::table('resources')->where('user_id', $this->user->id)->delete();

        DB::table('folders')->where('user_id', $this->user->id)->delete();
    }
}
