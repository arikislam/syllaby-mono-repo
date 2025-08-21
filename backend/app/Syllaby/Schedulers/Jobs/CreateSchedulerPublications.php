<?php

namespace App\Syllaby\Schedulers\Jobs;

use Log;
use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use Illuminate\Bus\Batchable;
use App\Syllaby\Planner\Event;
use App\System\Enums\QueueType;
use App\Shared\Reminders\Reminder;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\Actions\PublisherAction;
use App\Syllaby\Publisher\Publications\Concerns\BuildsSocialPayload;
use App\Syllaby\Publisher\Publications\Actions\CreatePublicationAction;

class CreateSchedulerPublications implements ShouldBeUnique, ShouldQueue
{
    use Batchable, BuildsSocialPayload, Queueable;

    protected User $user;

    protected Video $video;

    protected ?int $order;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Video $video, ?int $order = null)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);

        $this->user = $user->withoutRelations();
        $this->video = $video->withoutRelations();
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (blank($this->video->scheduler_id)) {
            return;
        }

        $publication = app(CreatePublicationAction::class)->handle([
            'video_id' => $this->video->id,
        ], $this->user);

        $scheduler = $this->video->scheduler;
        $scheduler->load(['channels.account']);

        $occurrence = $this->fetchOccurrence($scheduler);

        $input = [
            'scheduler_id' => $scheduler->id,
            'post_type' => PostType::POST->value,
            'scheduled_at' => $occurrence->occurs_at,
            'description' => Arr::get($scheduler->metadata, 'custom_description'),
        ];

        if (Arr::get($scheduler->metadata, 'ai_labels', false) === true) {
            $input['ai_tags'] = $this->generateTags($this->video->faceless->script);
            $input['ai_description'] = $this->generateDescription($this->video->faceless->script);
        }

        $scheduler->channels->each(
            fn ($channel) => $this->schedule($occurrence, $channel, $publication, $input)
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        Log::withoutContext()->alert("CreateSchedulerPublications job failed for video ID: {$this->video->id}");
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): ?string
    {
        if (blank($this->video->scheduler_id)) {
            return null;
        }

        return "{$this->video->id}:{$this->video->scheduler_id}";
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            (new WithoutOverlapping($this->lockerKey()))->dontRelease()->expireAfter(30),
        ];
    }

    /**
     * Get the occurrence for the video.
     */
    private function fetchOccurrence(Scheduler $scheduler): Occurrence
    {
        if (blank($this->order)) {
            $this->order = $scheduler->videos()->where('id', '<=', $this->video->id)->oldest('id')->count() - 1;
        }

        return $scheduler->occurrences()->oldest('id')->offset($this->order)->limit(1)->first();
    }

    /**
     * Schedule the publication.
     */
    private function schedule(Occurrence $occurrence, SocialChannel $channel, Publication $publication, array $input): void
    {
        $provider = $channel->account->provider->toString();

        $payload = array_merge(
            $input, $this->buildPayload($provider, $occurrence->topic, $input)
        );

        $publication = app(PublisherAction::class)->handle($payload, $provider, $publication, $channel);

        $this->remind($publication->event);
    }

    /**
     * Get the lock key for the job.
     */
    private function lockerKey(): string
    {
        return "video:{$this->video->id}:scheduler:{$this->video->scheduler_id}:publications";
    }

    /**
     * Remind the user about the event.
     *
     * @throws Exception
     */
    private function remind(Event $event): void
    {
        $date = $event->starts_at->copy()->subHours(4);

        Reminder::set(Scheduler::REMINDER_KEY, $date, $event->id);
    }
}
