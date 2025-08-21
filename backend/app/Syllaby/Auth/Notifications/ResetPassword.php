<?php

namespace App\Syllaby\Auth\Notifications;

use App\Syllaby\Users\User;
use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    use Queueable;

    public string $url;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user, public ?string $token = null)
    {
        $this->url = $this->buildUrl($user);
    }

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
            ->subject('Password reset request for your syllaby account')
            ->view('emails.forgot-password', [
                'user' => $notifiable,
                'token' => $this->token,
                'url' => $this->url,
            ]);

        return tap($message, fn () => $this->attachments($message));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
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

    /**
     * Build the front-end destination page url.
     */
    private function buildUrl(object $notifiable): string
    {
        $email = urlencode($notifiable->getEmailForPasswordReset());

        return config('app.frontend_url')."/reset-password?token={$this->token}&email={$email}";
    }
}
