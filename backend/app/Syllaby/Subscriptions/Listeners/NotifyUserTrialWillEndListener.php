<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\Events\TrialWillEnd;
use App\Syllaby\Subscriptions\Actions\ExtendTrialAction;
use App\Syllaby\Subscriptions\Notifications\TrialExtended;
use App\Syllaby\Subscriptions\Notifications\TrialWillEndReminder;

class NotifyUserTrialWillEndListener implements ShouldQueue
{
    public function __construct(protected ExtendTrialAction $extends) {}

    /**
     * Handle the event.
     */
    public function handle(TrialWillEnd $event): void
    {
        $user = $event->user;
        $payload = $event->payload;
        $days = config('services.stripe.trial_days');

        if ($this->hasFullCredits($user) && $this->extends->handle($user, $days)) {
            $user->notify(new TrialExtended);

            return;
        }

        if ($this->trialSkipped($payload)) {
            return;
        }

        $user->notify(new TrialWillEndReminder);
    }

    /**
     * Checks whether the user has spent any credits during trial.
     */
    private function hasFullCredits(User $user): bool
    {
        return $user->remaining_credit_amount === $user->monthly_credit_amount;
    }

    /**
     * Check if the trial was manually ended.
     */
    private function trialSkipped(array $payload): bool
    {
        $date = Arr::get($payload, 'data.object.trial_end');

        return $date && (Carbon::parse($date)->isToday() === false);
    }
}
