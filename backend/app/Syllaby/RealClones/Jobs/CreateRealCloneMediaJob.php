<?php

namespace App\Syllaby\RealClones\Jobs;

use Exception;
use Throwable;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Log;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class CreateRealCloneMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected RealClone $clone)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->download();
            $this->markAsCompleted();
        } catch (Exception $error) {
            $this->fail($error);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Unable to create real clone {id} media record from {provider}', [
            'id' => $this->clone->id,
            'error' => $exception->getMessage(),
            'provider' => $this->clone->provider,
        ]);

        $this->clone->update([
            'status' => RealCloneStatus::SYNC_FAILED,
        ]);
    }

    /**
     * Download and creates a media record for the generated video.
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws FileCannotBeAdded
     */
    private function download(): void
    {
        $this->clone->addMediaFromUrl($this->clone->url)
            ->withAttributes(['user_id' => $this->clone->user_id])
            ->addCustomHeaders(['ACL' => 'public-read'])
            ->toMediaCollection('video');
    }

    /**
     * Marks the video as completed, registering the synced at time.
     */
    private function markAsCompleted(): void
    {
        $this->clone->update([
            'url' => null,
            'synced_at' => now(),
            'status' => RealCloneStatus::COMPLETED,
        ]);
    }
}
