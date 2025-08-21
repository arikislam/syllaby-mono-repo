<?php

namespace App\Syllaby\Subscriptions\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserSwappedPlans extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Plan $previousPlan) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return tap($this->resolveEmailMessage($notifiable), fn ($message) => $this->attachments($message));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'action' => $this->upgradedPlan($notifiable) ? 'upgraded' : 'downgraded',
            'previous' => [
                'name' => $this->previousPlan->name,
                'price' => $this->previousPlan->price,
                'interval' => $this->previousPlan->type,
            ],
            'current' => [
                'name' => $notifiable->plan->name,
                'price' => $notifiable->plan->price,
                'interval' => $notifiable->plan->type,
            ],
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
     * Decides whether send an upgrade or downgrade email.
     */
    private function resolveEmailMessage(object $user): MailMessage
    {
        if ($this->upgradedPlan($user)) {
            return $this->upgradeMessage($user);
        }

        return $this->downgradeMessage($user);
    }

    /**
     * Set the notification type value for database channel.
     */
    public function databaseType($notifiable): string
    {
        return 'user-swapped-plans';
    }

    /**
     * Check if the user upgraded plans.
     */
    private function upgradedPlan(object $user): bool
    {
        return $user->plan->price > $this->previousPlan->price;
    }

    /**
     * Upgrade email message.
     */
    private function upgradeMessage(object $user): MailMessage
    {
        return (new MailMessage)
            ->subject("Level Up with Syllaby – Welcome to Your Upgraded Plan, {$user->name}!")
            ->view('emails.subscriptions.upgrade', [
                'user' => $user,
                'plan' => $user->plan,
            ]);
    }

    /**
     * Downgrade email message.
     */
    private function downgradeMessage(object $user): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Syllaby Plan Has Been Updated – We're Still With You, {$user->name}!")
            ->view('emails.subscriptions.downgrade', [
                'user' => $user,
                'plan' => $user->plan,
            ]);
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
