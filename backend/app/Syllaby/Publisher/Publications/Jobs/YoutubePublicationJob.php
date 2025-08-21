<?php

namespace App\Syllaby\Publisher\Publications\Jobs;

use Illuminate\Support\Arr;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Services\Youtube\UploadService;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;

class YoutubePublicationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Publication $publication, public SocialChannel $channel)
    {
        $this->onConnection('publisher');
        $this->onQueue(QueueType::PUBLISH->value);
    }

    /**
     * Execute the job.
     */
    public function handle(UploadService $service): void
    {
        $response = $service->upload($this->publication, $this->channel);

        match (Arr::get($response, 'status')) {
            'success' => $this->success($response),
            'failed' => $this->abort($response),
        };

        dispatch(new LogPublicationsJob($this->publication, $this->channel, $response));
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        $this->abort(['errors' => [
            ['message' => 'We were unable to upload your video.'],
        ]]);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "youtube:{$this->publication->id}:{$this->channel->id}";
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['youtube', "publication:{$this->publication->id}", "channel:{$this->channel->id}"];
    }

    /**
     * Marks the publication as failed with a failure message.
     */
    private function abort(array $response): void
    {
        Log::info('YoutubePublicationJob aborted', ['response' => $response]);

        $this->publication->channels()->updateExistingPivot($this->channel, [
            'status' => SocialUploadStatus::FAILED->value,
            'error_message' => Arr::get($response, 'errors.0.message', 'Something went wrong.'),
        ]);
    }

    /**
     * Marks the publication as successful.
     */
    private function success(array $response): void
    {
        $this->publication->channels()->updateExistingPivot($this->channel, [
            'status' => SocialUploadStatus::COMPLETED->value,
            'provider_media_id' => Arr::get($response, 'response.id'),
        ]);

        $this->channel->user->notify(new PublicationSuccessful($this->publication, $this->channel));
    }
}
