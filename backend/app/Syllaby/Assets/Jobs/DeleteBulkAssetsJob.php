<?php

namespace App\Syllaby\Assets\Jobs;

use Throwable;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class DeleteBulkAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $assets,
        public readonly User $user,
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            Asset::query()
                ->whereIn('id', $this->assets)
                ->where('user_id', $this->user->id)
                ->where('status', '<>', AssetStatus::PROCESSING)
                ->whereDoesntHave('videos')
                ->chunkById(50, function ($assets) {
                    foreach ($assets as $asset) {
                        /** @var Asset $asset */
                        $asset->videos()->detach(); // Just in case there are any orphan associations
                        $asset->delete();
                    }
                });
        });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Bulk asset deletion job failed', [
            'user_id' => $this->user,
            'asset_ids' => $this->assets,
            'error' => $exception->getTraceAsString(),
        ]);
    }
}
