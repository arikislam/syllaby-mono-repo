<?php

namespace App\Syllaby\Credits\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\Syllaby\Credits\CreditHistory;

class CreditsRefundedNotification extends Notification
{
    use Queueable;

    public function __construct(public CreditHistory $history) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'credits-refunded';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => $this->history->creditable_type,
            'description' => $this->history->description,
            'label' => $this->history->label,
            'amount' => $this->history->amount,
            'message' => 'Credits are being refunded to your account.',
        ];
    }
}
