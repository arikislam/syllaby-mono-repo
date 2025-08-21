<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUserMedia implements ShouldQueue
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
        Media::with('model')->where('user_id', $this->user->id)->chunkById(100, function ($media) {
            $media->each(function ($media) {
                $media->model?->delete();
                $media->delete();
            });
        });
    }
}
