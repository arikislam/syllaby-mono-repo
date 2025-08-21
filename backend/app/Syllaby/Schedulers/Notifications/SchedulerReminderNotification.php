<?php

namespace App\Syllaby\Schedulers\Notifications;

use Illuminate\Support\Arr;
use App\System\Notification;
use App\Syllaby\Videos\Video;
use Illuminate\Bus\Queueable;
use App\Syllaby\Planner\Event;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

class SchedulerReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Event $event, protected Publication $publication, protected ?Video $video)
    {
        $this->event->withoutRelations();
        $this->publication->withoutRelations();
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
            ->subject("Your Video {$this->video->title} is Ready to Publish!")
            ->view('emails.schedulers.reminder', [
                'user' => $notifiable,
                'event' => $this->event,
                'video' => $this->video,
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
            'event' => [
                'id' => $this->event->id,
                'starts_at' => $this->event->starts_at,
                'ends_at' => $this->event->ends_at,
                'model_type' => $this->event->model_type,
                'model_id' => $this->event->model_id,
            ],
            'publication' => [
                'id' => $this->publication->id,
            ],
            'video' => [
                'id' => $this->video->id,
                'title' => $this->video->title,
                'type' => $this->video->type,
                'scheduler_id' => $this->video->scheduler_id,
            ],
        ];
    }

    /**
     * Set the notification type value for database channel.
     */
    public function databaseType(object $notifiable): string
    {
        return 'scheduler-reminder';
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

        if (blank($this->video)) {
            return false;
        }

        return in_array($channel, $notifiable->channelsFor('scheduler.reminders'));
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
     * Build the url for the reminder notification.
     */
    private function buildUrl(): string
    {
        $params = Arr::query([
            'calendar' => true,
            'createpost' => true,
            'publication-id' => $this->publication->id,
            'status' => SocialUploadStatus::SCHEDULED->value,
        ]);

        return config('app.frontend_url')."/publish?{$params}";
    }
}
