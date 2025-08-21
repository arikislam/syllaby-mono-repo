<?php

namespace App\Syllaby\Publisher\Publications\Jobs;

use Exception;
use App\Syllaby\Planner\Event;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Vendors\Publisher;
use App\Syllaby\Publisher\Channels\Vendors\Individual\Factory;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class PublishScheduledPublications implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Event $event)
    {
        $this->onConnection('publisher');
        $this->onQueue(QueueType::PUBLISH->value);
    }

    /**
     * Execute the job.
     */
    public function handle(Factory $factory): void
    {
        if (blank($this->event)) {
            return;
        }

        $this->event->load(['model.channels.account', 'user']);

        $publication = $this->event->model;
        $publication->channels->each(fn ($channel) => $this->publish($channel, $publication, $factory));
    }

    /**
     * Handle a job failure.
     */
    public function uniqueId(): string
    {
        return $this->event->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["publish:scheduled:{$this->event->id}"];
    }

    /**
     * Publish the publication to the channel
     */
    private function publish(SocialChannel $channel, Publication $publication, Factory $factory): void
    {
        $provider = $channel->account->provider->toString();

        if (filled($publication->video_id) && ! $publication->video->status->isCompleted()) {
            return;
        }

        if ($channel->pivot->status === SocialUploadStatus::COMPLETED->value) {
            return;
        }

        try {
            if (! $factory->for($provider)->validate($channel)) {
                $factory->for($provider)->refresh($channel->account);
            }

            Publisher::driver($provider)->publish($publication, $channel, $channel->pivot->post_type);
        } catch (InvalidRefreshTokenException $exception) {
            $this->log($publication, $channel, $provider, $exception->getMessage());
            $this->markAsFailed($publication, $channel);
        } catch (Exception $exception) {
            $this->log($publication, $channel, $provider, $exception->getMessage());
            $this->markAsFailed($publication, $channel);
        }
    }

    /**
     * Mark the publication as fail
     */
    private function markAsFailed(Publication $publication, SocialChannel $channel): void
    {
        $publication->channels()->wherePivot('social_channel_id', $channel->id)->update([
            'status' => SocialUploadStatus::FAILED->value,
        ]);
    }

    /**
     * Write the log message
     */
    private function log(Publication $publication, SocialChannel $channel, string $provider, string $message): void
    {
        Log::alert('Failed Publishing Scheduled Publication', [
            'message' => $message,
            'provider' => $provider,
            'channel' => $channel->id,
            'publication' => $publication->id,
        ]);
    }
}
