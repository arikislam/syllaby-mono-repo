<?php

namespace App\Console\Commands;

use Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;
use App\Shared\Slack\Notifications\SubscriptionAlert;

class SendSubscriptionAlerts extends Command
{
    protected $signature = 'syllaby:send-subscription-alerts';

    protected $description = 'Send Alerts to Slack for Subscriptions that have completed 3 months.';

    public function handle(): void
    {
        $active = Subscription::query()->active()
            ->join('users', 'subscriptions.user_id', '=', 'users.id')
            ->whereDate('subscriptions.created_at', '=', Carbon::now()->subMonths(3)->toDateString())
            ->pluck('users.email', 'users.name')
            ->all();

        if (blank($active)) {
            return;
        }

        Notification::route('slack', config('services.slack_alerts.subscriptions'))->notify(new SubscriptionAlert($active));
    }

}
