<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class DeleteUserAssetsChunk implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        protected User $user,
        protected array $assets,
    ) {}

    public function handle(): void
    {
        $assets = Asset::where('user_id', $this->user->id)->whereIn('id', $this->assets)->get();

        if ($assets->isEmpty()) {
            return;
        }

        $assets->each(fn (Asset $asset) => $asset->delete());
    }

    public function middlewares(): array
    {
        return [
            new SkipIfBatchCancelled,
        ];
    }
}
