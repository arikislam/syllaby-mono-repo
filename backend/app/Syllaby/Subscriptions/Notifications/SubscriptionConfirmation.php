<?php

namespace App\Syllaby\Subscriptions\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionConfirmation extends Notification implements ShouldQueue
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
        $notifiable->load(['plan', 'subscriptions.owner']);

        $trialDays = $notifiable->plan->trialDays($notifiable);
        $subscription = $notifiable->subscription();
        $invoice = $subscription->upcomingInvoice();

        $message = (new MailMessage)
            ->subject('Welcome to Effortless Content Creation with Syllaby!')
            ->view('emails.subscriptions.confirmation', [
                'user' => $notifiable,
                'trialDays' => $trialDays,
                'plan' => $notifiable->plan,
                'subscription' => $subscription,
                'invoice' => [
                    'amount' => $invoice->amountDue(),
                    'date' => $invoice->date()?->format('m-d-Y'),
                ],
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
     * Determine if the notification should be sent.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (! parent::shouldSend($notifiable, $channel)) {
            return false;
        }

        return $notifiable->subscribed();
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
