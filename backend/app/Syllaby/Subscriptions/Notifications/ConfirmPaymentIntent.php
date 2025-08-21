<?php

namespace App\Syllaby\Subscriptions\Notifications;

use Illuminate\Support\Arr;
use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ConfirmPaymentIntent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected array $payload) {}

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
            ->subject('Confirm Payment - Syllaby')
            ->view('emails.subscriptions.confirm-payment', [
                'user' => $notifiable,
                'url' => Arr::get($this->payload, 'data.object.hosted_invoice_url'),
            ]);

        $this->attachments($message);

        return $message;
    }

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
            $this->addEmailAsset('email/banners/cancel.png', 'banner.png'),
        ];

        collect($files)->each(fn ($file) => $message->attach($file));
    }
}
