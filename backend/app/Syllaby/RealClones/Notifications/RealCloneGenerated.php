<?php

namespace App\Syllaby\RealClones\Notifications;

use Illuminate\Support\Str;
use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RealCloneGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected RealClone $clone)
    {
        $this->clone->load([
            'footage:id,video_id',
            'generator:id,topic,model_id,model_type',
        ]);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return $notifiable->channelsFor('real_clones');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $generator = $this->clone->generator;
        $title = $generator?->topic ?? Str::limit($this->clone->script, 24);

        $message = (new MailMessage)
            ->subject("Video Ready - {$title}")
            ->view('emails.real-clone-complete', [
                'title' => $title,
                'user' => $notifiable,
                'editor_url' => $this->buildDestinationUrl(),
            ]);

        return tap($message, fn () => $this->attachments($message));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'real_clone' => [
                'id' => $this->clone->id,
                'status' => $this->clone->status,
                'footage_id' => $this->clone->footage_id,
                'provider_id' => $this->clone->provider_id,
                'provider_name' => $this->clone->provider,
                'video_id' => $this->clone->footage->video_id,
                'script' => Str::limit($this->clone->script, 24),
            ],
            'context' => [
                'id' => $this->clone->generator?->id,
                'topic' => $this->clone->generator?->topic,
            ],
        ];
    }

    /**
     * Set the notification type value for database channel.
     */
    public function databaseType(object $notifiable): string
    {
        return 'video-generated';
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
     * Build the front-end destination page url.
     */
    private function buildDestinationUrl(): string
    {
        return config('app.frontend_url')."/content/{$this->clone->footage->video_id}";
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
