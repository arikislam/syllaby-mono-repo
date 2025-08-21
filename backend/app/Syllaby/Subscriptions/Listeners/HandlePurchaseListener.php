<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Notification;
use Illuminate\Support\Arr;
use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Support\Facades\Mail;
use App\Syllaby\Subscriptions\Purchase;
use App\Syllaby\Clonables\Enums\CloneStatus;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\Events\CheckoutCompleted;
use App\Syllaby\Clonables\Jobs\ProcessAvatarCloneIntent;
use App\Syllaby\Clonables\Mails\UserRequestedAvatarClone;
use App\Syllaby\Subscriptions\Actions\CreatePurchaseAction;
use App\Syllaby\Clonables\Notifications\AvatarCloneRequested;
use App\Syllaby\Clonables\Notifications\AvatarCloneCheckoutCompleted;

readonly class HandlePurchaseListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private CreditService $credits,
        private CreatePurchaseAction $purchases,
    )
    {
    }

    /**
     * Handle the event.
     */
    public function handle(CheckoutCompleted $event): void
    {
        $user = $event->user;
        $checkout = Arr::get($event->payload, 'data.object');

        if (Arr::get($checkout, 'mode') !== 'payment') {
            return;
        }

        if (!$price = $this->fetchPrice($checkout)) {
            return;
        }

        $purchase = $this->purchases->handle($user, $price, $checkout);

        match (Arr::get($checkout, 'metadata.action')) {
            'real-clone-avatar' => $this->confirmClonePurchase($user, $purchase, $checkout),
            'extra-credits' => $this->credits->applyExtraCredits($user, $price, $checkout),
            default => Log::error('Unrecognized purchase action')
        };
    }

    /**
     * Fetch the plan with the given id.
     */
    private function fetchPrice(array $checkout): ?Plan
    {
        return Plan::active()->where('plan_id', Arr::get($checkout, 'metadata.price_id'))->first();
    }

    /**
     * Associates the current purchase to clonable model.
     */
    private function confirmClonePurchase(User $user, Purchase $purchase, array $checkout): void
    {
        if (!$id = Arr::get($checkout, 'metadata.clonable_id')) {
            return;
        }

        if (!$clonable = Clonable::where('user_id', $user->id)->where('id', $id)->first()) {
            return;
        }

        $clonable = tap($clonable)->update([
            'purchase_id' => $purchase->id,
            'status' => CloneStatus::REVIEWING,
        ]);

        $user->notify(new AvatarCloneCheckoutCompleted($clonable));

        foreach (['customerrequest@syllaby.io', 'awais@syllaby.io'] as $recipient) {
            Mail::to($recipient)->send(new UserRequestedAvatarClone($clonable, $user));
        }

        Notification::route('slack', config('services.slack_alerts.real_clone_request'))->notify(new AvatarCloneRequested($clonable, $user));
    }
}
