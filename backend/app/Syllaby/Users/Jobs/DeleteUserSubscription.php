<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionItem;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\CardFingerprint;

class DeleteUserSubscription implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Take care of Google Play Subscriptions
        if (! $this->user->hasStripeId()) {
            return;
        }

        if (! $this->user->subscriptions()->exists()) {
            return;
        }

        $subscription = $this->user->subscription();
        $subscription->loadMissing('owner');

        if (! $subscription->ended()) {
            $subscription->cancelNow();
        }

        tap($this->user->subscriptions()->pluck('id'), function ($ids) {
            Subscription::destroy($ids);
            SubscriptionItem::whereIn('subscription_id', $ids)->delete();
        });

        $this->user->stripe()->customers->delete($this->user->stripe_id);
        CardFingerprint::where('user_id', $this->user->id)->delete();
        $this->user->update(['stripe_id' => null]);
    }
}
