<?php

namespace App\Syllaby\Subscriptions\Notifications;

use Illuminate\Support\Arr;
use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\JVZooSubscription;
use Illuminate\Notifications\Messages\MailMessage;

class JVZooOnboardingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private JVZooTransaction $transaction, private JVZooSubscription $subscription) {}

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
        $query = Arr::query([
            'email' => $notifiable->email,
            'token' => $this->transaction->onboarding_token,
        ]);

        $url = config('app.frontend_url')."/jvzoo/signup?{$query}";

        $message = (new MailMessage)
            ->subject('Welcome to Syllaby - Set Your Password')
            ->view('emails.subscriptions.jvzoo-onboarding', [
                'url' => $url, 'user' => $notifiable,
            ]);

        $this->attachments($message);

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
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
            $this->addEmailAsset('email/shared/gift.png', 'gift.png'),
            $this->addEmailAsset('email/banners/cancel.png', 'banner.png'),
        ];

        collect($files)->each(fn ($file) => $message->attach($file));
    }
}
