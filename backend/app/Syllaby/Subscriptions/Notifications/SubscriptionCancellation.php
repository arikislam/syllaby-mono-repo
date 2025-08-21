<?php

namespace App\Syllaby\Subscriptions\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionCancellation extends Notification implements ShouldQueue
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
            ->subject('Your subscription has been cancelled')
            ->view('emails.subscriptions.cancellation', [
                'user' => $notifiable,
                'endDate' => $notifiable->subscription()->ends_at->format('l, M d'),
                'hasOffer' => $this->canRedeemOffer($notifiable),
                'subscriptionsPageUrl' => config('app.frontend_url').'/my-account/subscription',
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

    /**
     * Determine if the user can redeem the offer.
     */
    private function canRedeemOffer(object $notifiable): bool
    {
        $coupon = config('services.stripe.unsub_coupon');

        return $notifiable->coupons()->where('code', $coupon)->doesntExist();
    }
}
