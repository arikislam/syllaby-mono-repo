<?php

namespace App\Syllaby\Videos\Notifications;

use Illuminate\Support\Str;
use App\Syllaby\Ideas\Topic;
use App\System\Notification;
use App\Syllaby\Videos\Video;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VideoFootageRendered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Video $video) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return $notifiable->channelsFor('videos');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Video: {$this->video->title} - successfully rendered")
            ->view('emails.video-render-complete', [
                'user' => $notifiable,
                'video' => $this->video,
                'related_topics' => $this->relatedTopics(),
                'destination_url' => $this->buildDestinationUrl(),
                'video_url' => $this->video->getFirstMediaUrl('video'),
            ]);

        return tap($message, fn () => $this->attachments($message));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $attributes = [
            'video' => [
                'id' => $this->video->id,
                'title' => $this->video->title,
                'type' => $this->video->type,
                'status' => $this->video->status,
                'provider_id' => $this->video->provider_id,
                'provider_name' => $this->video->provider,
            ],
        ];

        if ($this->video->type === Video::FACELESS) {
            $attributes['faceless'] = [
                'id' => $this->video->faceless->id,
                'type' => $this->video->faceless->type,
            ];
        }

        return $attributes;
    }

    /**
     * Set the notification type value for database channel.
     */
    public function databaseType(object $notifiable): string
    {
        return 'video-footage-rendered';
    }

    /**
     * Determine which queues should be used for each notification channel.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => QueueType::EMAIL->value,
        ];
    }

    /**
     * Fetch related topics for the video if it's of type 'faceless'.
     */
    private function relatedTopics(): array
    {
        if ($this->video->type !== Video::FACELESS) {
            return [];
        }

        $script = $this->video->faceless()->first('script')->script;
        $title = Str::limit($script, 250, '', true);

        $topic = Topic::where('user_id', $this->video->user_id)
            ->where('hash', md5($title))
            ->first();

        return $topic?->ideas ?? [];
    }

    /**
     * Build the front-end destination page url.
     */
    private function buildDestinationUrl(): string
    {
        return sprintf('%s/preview/%s', config('app.frontend_url'), $this->video->id);
    }

    /**
     * Email assets attachments.
     */
    private function attachments(MailMessage $message): void
    {
        $files = [
            ...$this->defaultAttachments(),
            $this->addEmailAsset('email/banners/base.png', 'banner.png'),
        ];

        collect($files)->each(fn ($file) => $message->attach($file));
    }
}
