<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\Contracts\SubscriptionEventHook;

class RecordStripeLogsListener
{
    /**
     * Handle the event.
     */
    public function handle(SubscriptionEventHook $event): void
    {
        DB::table('stripe_event_logs')->insert([
            'user_id' => $event->user->id,
            'event_type' => Arr::get($event->payload, 'type'),
            'payload' => json_encode($event->payload),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }
}
