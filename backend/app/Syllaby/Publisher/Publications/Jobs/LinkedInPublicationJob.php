<?php

namespace App\Syllaby\Publisher\Publications\Jobs;

use Exception;
use Throwable;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Services\LinkedIn\UploadService;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;

class LinkedInPublicationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Publication $publication, protected SocialChannel $channel)
    {
        $this->onConnection('publisher');
        $this->onQueue(QueueType::PUBLISH->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $invokable = new UploadService($this->publication, $this->channel);
            $shareId = $invokable->upload();
            filled($shareId) ? $this->success($shareId) : $this->abort();

            dispatch(new LogPublicationsJob($this->publication, $this->channel, ['share_id' => $shareId]));
        } catch (Exception $exception) {
            $this->fail($exception);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::alert('LinkedIn Publication with channel-id {channel} - publication-id {publication} failed.', [
            'channel' => $this->channel->id,
            'publication' => $this->publication->id,
            'error' => $exception->getMessage(),
        ]);

        $this->abort();

        dispatch(new LogPublicationsJob($this->publication, $this->channel, ['error' => $exception->getMessage()]));
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "linkedin:{$this->publication->id}:{$this->channel->id}";
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['linkedin', "publication:{$this->publication->id}", "channel:{$this->channel->id}"];
    }

    private function success(string $shareId): void
    {
        $this->publication->channels()->updateExistingPivot($this->channel, [
            'status' => SocialUploadStatus::COMPLETED->value,
            'provider_media_id' => $shareId,
        ]);

        $this->channel->user->notify(new PublicationSuccessful($this->publication, $this->channel));
    }

    private function abort(string $status = 'Something went wrong'): void
    {
        $this->publication->channels()->updateExistingPivot($this->channel, [
            'status' => SocialUploadStatus::FAILED->value,
            'error_message' => $status,
        ]);
    }
}
