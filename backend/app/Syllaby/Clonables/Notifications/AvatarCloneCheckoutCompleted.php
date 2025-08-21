<?php

namespace App\Syllaby\Clonables\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AvatarCloneCheckoutCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Clonable $clonable) {}

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
        $this->clonable->load('purchase.plan');
        $product = $this->clonable->purchase->plan->name;

        $message = (new MailMessage)
            ->subject('Your purchase of a real clone is confirmed!')
            ->view('emails.avatar-clone.checkout', [
                'product' => $product,
                'user' => $notifiable,
            ]);

        return tap($message, fn () => $this->attachments($message));
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
