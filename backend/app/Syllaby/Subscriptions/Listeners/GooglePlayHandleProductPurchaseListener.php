<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Clonables\Enums\CloneStatus;
use App\Syllaby\Clonables\Mails\UserRequestedAvatarClone;
use App\Syllaby\Clonables\Notifications\AvatarCloneCheckoutCompleted;
use App\Syllaby\Clonables\Notifications\AvatarCloneRequested;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Subscriptions\Events\GooglePlayProductPurchased;
use App\Syllaby\Subscriptions\GooglePlayPurchase;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

readonly class GooglePlayHandleProductPurchaseListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     */
    public function handle(GooglePlayProductPurchased $event): void
    {
        $user = $event->user;
        $rtdn = $event->rtdn;
        $plan = $rtdn->plan;

        if (!$plan) {
            Log::warning('Google Play product purchase event without plan', [
                'rtdn_id' => $rtdn->id,
                'user_id' => $user->id,
            ]);
            return;
        }

        // Get the Google Play purchase record created by verification service
        $googlePlayPurchase = $this->getGooglePlayPurchase($rtdn);

        if (!$googlePlayPurchase) {
            Log::warning('Google Play purchase record not found, verification may not be complete', [
                'rtdn_id'        => $rtdn->id,
                'user_id'        => $user->id,
                'purchase_token' => $rtdn->purchase_token,
            ]);
            return;
        }

        // Handle different product types based on plan metadata
        $productType = $plan->meta['type'] ?? null;

        match ($productType) {
            'real-clone-avatar' => $this->confirmClonePurchase($user, $rtdn),
            'voice-clone' => $this->confirmClonePurchase($user, $rtdn),
            'extra-credits' => $this->handleExtraCredits($user, $plan),
            default => $this->handleGenericProductPurchase($user, $plan, $rtdn),
        };

        Log::info('Google Play product purchase processed', [
            'rtdn_id'      => $rtdn->id,
            'user_id'      => $user->id,
            'plan_id'      => $plan->id,
            'product_type' => $productType,
        ]);
    }

    /**
     * Get existing Google Play purchase record.
     */
    private function getGooglePlayPurchase($rtdn): ?GooglePlayPurchase
    {
        return GooglePlayPurchase::where('purchase_token', $rtdn->purchase_token)->first();
    }


    /**
     * Handle extra credits purchase.
     */
    private function handleExtraCredits(User $user, Plan $plan): void
    {
        // Get credits from plan metadata
        $credits = $plan->meta['credits'] ?? 0;

        if ($credits <= 0) {
            Log::warning('Google Play product purchase has no credits to add', [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
            ]);
            return;
        }

        // Apply credits to user account using increment method
        $creditService = new CreditService($user);
        $creditService->applyExtraCredits(
            user: $user,
            plan: $plan,
            checkout: [],
        );
        Log::info('Google Play extra credits applied', [
            'user_id'       => $user->id,
            'credits_added' => $credits,
            'plan_id'       => $plan->id,
        ]);
    }

    /**
     * TODO: Refactor this method to handle clone purchases more cleanly. Not a proper implementation yet.
     * Associates the current purchase to clonable model.
     */
    private function confirmClonePurchase(User $user, $rtdn): void
    {
        // Look for clonable ID in plan metadata or RTDN response
        $clonableId = $rtdn->plan->meta['clonable_id'] ??
            $rtdn->rtdn_response['clonable_id'] ?? null;

        if (!$clonableId) {
            Log::warning('Clone purchase without clonable_id', [
                'rtdn_id' => $rtdn->id,
                'user_id' => $user->id,
            ]);
            return;
        }

        $clonable = Clonable::where('user_id', $user->id)
            ->where('id', $clonableId)
            ->first();

        if (!$clonable) {
            Log::warning('Clonable not found for clone purchase', [
                'rtdn_id'     => $rtdn->id,
                'user_id'     => $user->id,
                'clonable_id' => $clonableId,
            ]);
            return;
        }

        $clonable = tap($clonable)->update([
            'status' => CloneStatus::REVIEWING,
        ]);

        $user->notify(new AvatarCloneCheckoutCompleted($clonable));

        foreach (['customerrequest@syllaby.io', 'awais@syllaby.io'] as $recipient) {
            Mail::to($recipient)->send(new UserRequestedAvatarClone($clonable, $user));
        }

        Notification::route('slack', config('services.slack_alerts.real_clone_request'))
            ->notify(new AvatarCloneRequested($clonable, $user));
    }

    /**
     * Handle generic product purchases (like extra credits).
     */
    private function handleGenericProductPurchase(User $user, Plan $plan, $rtdn): void
    {
        // Log the generic product purchase
        Log::info('Generic Google Play product purchase handled', [
            'rtdn_id' => $rtdn->id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        // Add any generic product purchase logic here
        // For example, you might want to send a confirmation email
        // or trigger other business logic specific to your app
    }
} 