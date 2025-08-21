<?php

namespace App\Syllaby\Folders\Jobs;

use Exception;
use App\Syllaby\Videos\Video;
use App\Syllaby\Folders\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Videos\Actions\DeleteVideoAction;

class RemoveResourceFromStorage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Resource $resource, private readonly bool $deleteUnusedAssets) {}

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        match (true) {
            $this->isVideo() => $this->removeVideo(),
            default => $this->removeFolder(),
        };
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["remove-resource:{$this->resource->id}"];
    }

    /**
     * @throws Exception
     */
    private function removeVideo(): void
    {
        app(DeleteVideoAction::class)->handle($this->resource->model, $this->deleteUnusedAssets);
    }

    /**
     * Removes a folder and its associated resource.
     */
    private function removeFolder(): void
    {
        DB::transaction(function () {
            $this->resource->model->delete();
            $this->resource->delete();
        });
    }

    /**
     * Determine if the resource is of type Video.
     */
    private function isVideo(): bool
    {
        return morph_type($this->resource->model_type, Video::class);
    }
}
