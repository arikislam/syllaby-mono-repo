<?php

namespace App\Syllaby\Subscriptions\Notifications;

use App\System\Notification;
use Laravel\Cashier\Cashier;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\Enums\PlanType;
use Illuminate\Notifications\Messages\MailMessage;

class TrialWillEndReminder extends Notification implements ShouldQueue
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

        $plan = $notifiable->plan;
        $subscription = $notifiable->subscription();
        $invoice = $subscription->upcomingInvoice();

        $message = (new MailMessage)
            ->subject('Your Syllaby Trial Ends in 3 Days')
            ->view('emails.subscriptions.trial-will-end', [
                'user' => $notifiable,
                'plan' => [
                    'name' => $plan->name,
                    'price' => Cashier::formatAmount($plan->price),
                    'recurrence' => PlanType::from($plan->type)->label(),
                ],
                'subscription' => $subscription,
                'invoice' => [
                    'amount' => $invoice->amountDue(),
                ],
            ]);

        $this->attachments($message);

        return $message;
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
