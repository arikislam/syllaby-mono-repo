<?php

namespace App\Syllaby\Schedulers\Notifications;

use Illuminate\Support\Arr;
use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use App\Syllaby\Folders\Resource;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SchedulerPublishingStarted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Scheduler $scheduler, protected Resource $resource)
    {
        $this->scheduler->withoutRelations();
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Bulk Scheduler Complete – Review Your Videos!')
            ->view('emails.schedulers.publishing', [
                'user' => $notifiable,
                'url' => $this->buildUrl(),
            ]);

        return tap($message, fn () => $this->attachments($message));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'scheduler' => [
                'id' => $this->scheduler->id,
                'name' => $this->scheduler->title,
                'topic' => $this->scheduler->topic,
                'type' => $this->scheduler->type,
            ],
            'resource' => [
                'id' => $this->resource->id,
                'name' => $this->resource->name,
                'parent_id' => $this->resource->parent_id,
            ],
        ];
    }

    /**
     * Set the notification type value for database channel.
     */
    public function databaseType(object $notifiable): string
    {
        return 'scheduler-publishing';
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
     * Determine if the notification should be sent.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (! parent::shouldSend($notifiable, $channel)) {
            return false;
        }

        return in_array($channel, $notifiable->channelsFor('scheduler.generated'));
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

    /**
     * Build the URL for the notification.
     */
    private function buildUrl(): string
    {
        $params = Arr::query([
            'filter' => 'all',
            'folder' => $this->resource->parent_id,
        ]);

        return config('app.frontend_url')."/content?{$params}";
    }
}
