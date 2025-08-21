<?php

namespace App\System;

use App\System\Traits\EmailAssets;
use App\Syllaby\Loggers\Suppression;
use Illuminate\Notifications\Notification as BaseNotification;

class Notification extends BaseNotification
{
    use EmailAssets;

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel !== 'mail') {
            return true;
        }

        if (! $record = Suppression::where('email', $notifiable->email)->first()) {
            return true;
        }

        return match (true) {
            $this->shouldSuppress($record) => false,
            $this->shouldSuppressSoftBounce($record) => false,
            default => true,
        };
    }

    /**
     * Determine if the record should be suppressed.
     */
    private function shouldSuppress(object $record): bool
    {
        return filled($record->complained_at) || (filled($record->bounced_at) && $record->bounce_type === 'Permanent');
    }

    /**
     * Determine if the record should be suppressed due to a soft bounce.
     */
    private function shouldSuppressSoftBounce(object $record): bool
    {
        return filled($record->bounced_at) && $record->bounce_type === 'Transient' && $record->soft_bounce_count >= 3;
    }
}
