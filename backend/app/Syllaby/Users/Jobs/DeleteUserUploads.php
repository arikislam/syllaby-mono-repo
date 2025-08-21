<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Tags\Tag;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Enums\AssetType;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;

class DeleteUserUploads implements ShouldQueue
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
        // TODO: Use lazy deletion or chunking
        Asset::where('type', AssetType::AUDIOS->value)
            ->where('user_id', $this->user->id)
            ->chunkById(50, fn (Collection $assets) => $assets->each(fn (Asset $asset) => $asset->delete()));

        Tag::where('user_id', $this->user->id)->get()->each(function (Tag $tag) {
            $tag->media()->detach();
            $tag->templates()->detach();
            $tag->delete();
        });
    }
}
