<?php

namespace App\Syllaby\Videos\Commands;

use App\Syllaby\Videos\Video;
use Illuminate\Console\Command;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Builder;
use App\Syllaby\Videos\Actions\TimeoutVideoAction;

class VideoTimeoutMonitor extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'videos:timeout-monitor';

    /**
     * The console command description.
     */
    protected $description = 'Monitor videos stuck in processing for more than 24 hours and mark them as failed';

    /**
     * The statuses that are not considered as failed.
     */
    protected array $statuses = [
        VideoStatus::SYNCING->value,
        VideoStatus::RENDERING->value,
        VideoStatus::SYNC_FAILED->value,
    ];

    /**
     * Execute the console command.
     */
    public function handle(TimeoutVideoAction $timeout): void
    {
        $videos = $this->buildVideosQuery()->lazyByIdDesc(50)->each(fn ($video) => $timeout->handle($video));

        $this->info('Videos monitored successfully.');
    }

    /**
     * Build the base query to fetch monitor videos.
     */
    private function buildVideosQuery(): Builder
    {
        return Video::query()->whereIn('status', $this->statuses)
            ->where('updated_at', '<', now()->subDay())
            ->latest();
    }
}
