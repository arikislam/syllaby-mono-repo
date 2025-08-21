<?php

namespace App\Syllaby\Subscriptions\Notifications;

use Carbon\Carbon;
use App\System\Notification;
use Laravel\Cashier\Cashier;
use Laravel\Pennant\Feature;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Laravel\Cashier\Subscription;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionStorageUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Subscription $subscription, public int $quantity, public string $price) {}

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
        $plan = Plan::where('plan_id', $this->price)->first();
        $this->subscription->setRelation('owner', $notifiable);
        $billingDate = $this->subscription->asStripeSubscription()->current_period_end;

        $message = (new MailMessage)
            ->subject('Your Syllaby Storage was updated!')
            ->view('emails.subscriptions.storage-update', [
                'user' => $notifiable,
                'quantity' => $this->quantity,
                'amount' => Cashier::formatAmount($plan->price * $this->quantity),
                'storage' => Feature::for($notifiable)->value('max_storage'),
                'billing_date' => Carbon::createFromTimestamp($billingDate)->format('M d, Y'),
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
