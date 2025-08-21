<?php

namespace App\Syllaby\Clonables\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AvatarCloneGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your Real Clone Has Been Successfully Generated!')
            ->view('emails.avatar-clone.generated', [
                'user' => $notifiable,
                'cta_url' => $this->buildDestinationUrl(),
            ]);

        return tap($message, fn () => $this->attachments($message));
    }

    /**
     * Build the front-end destination page url.
     */
    private function buildDestinationUrl(): string
    {
        return config('app.frontend_url').'/clone';
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
