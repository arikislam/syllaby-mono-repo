<?php

namespace App\Syllaby\Publisher\Publications\Notifications;

use Illuminate\Support\Str;
use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Notifications\Messages\MailMessage;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\AccountPublication;

class PublicationSuccessful extends Notification implements ShouldQueue
{
    use Queueable;

    protected AccountPublication $details;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Publication $publication, protected SocialChannel $channel)
    {
        $this->details = AccountPublication::where('social_channel_id', $this->channel->id)
            ->where('publication_id', $publication->id)
            ->first();
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return $notifiable->channelsFor('publications');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)->subject('Publication Published')
            ->view('emails.publication-published', [
                'user' => $notifiable,
                'channel' => $this->channel,
                'url' => config('app.frontend_url').'/publish',
                'title' => Str::limit($this->details->name(), 120),
            ]);

        return tap($message, fn () => $this->attachments($message));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {

        return [
            'publication' => [
                'id' => $this->publication->id,
                'title' => $this->details->name(),
                'name' => $this->publication->video?->title ?? 'External Upload',
            ],
            'account' => [
                'id' => $this->channel->id,
                'type' => $this->channel->type,
                'name' => $this->channel->name,
                'provider' => $this->channel->account->provider->name,
            ],
        ];
    }

    /**
     * Get the type of the notification.
     */
    public function databaseType($notifiable): string
    {
        return 'publication-successful';
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
