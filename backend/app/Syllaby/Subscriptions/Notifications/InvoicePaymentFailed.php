<?php

namespace App\Syllaby\Subscriptions\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InvoicePaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected array $invoice) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your Payment Was Declined - Syllaby')
            ->view('emails.subscriptions.payment-failed', [
                'user' => $notifiable,
                'url' => config('app.frontend_url').'/my-account/subscription',
            ]);

        $this->attachments($message);

        return $message;
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
            $this->addEmailAsset('email/shared/gift.png', 'gift.png'),
            $this->addEmailAsset('email/banners/cancel.png', 'banner.png'),
        ];

        collect($files)->each(fn ($file) => $message->attach($file));
    }
}
