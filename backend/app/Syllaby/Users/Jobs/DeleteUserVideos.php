<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Videos\Actions\DeleteVideoAction;

class DeleteUserVideos implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user, protected bool $sync = false) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Video::where('user_id', $this->user->id)->chunkById(100, function (Collection $videos) {
            $videos->each(fn (Video $video) => app(DeleteVideoAction::class)->handle($video, sync: $this->sync));
        });
    }
}
