<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\Events\GooglePlayRtdnHandled;
use App\Syllaby\Subscriptions\Enums\GooglePlayVoidNotification;
use App\Syllaby\Subscriptions\Enums\GooglePlayProductNotification;
use App\Syllaby\Subscriptions\Events\GooglePlayProductPurchased;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionPaused;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionRenewed;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionCanceled;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionPurchased;
use App\Syllaby\Subscriptions\Services\GooglePlayVerificationService;
use App\Syllaby\Subscriptions\Enums\GooglePlaySubscriptionNotification;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionPlanChanged;

class GooglePlayRtdnHandledListener
{
    /**
     * Mapping of subscription notification types to their event classes.
     */
    private const SUBSCRIPTION_EVENT_MAP = [
        'isPurchase' => GooglePlaySubscriptionPurchased::class,
        'isRenewal' => GooglePlaySubscriptionRenewed::class,
        'isCancellation' => GooglePlaySubscriptionCanceled::class,
        'isPaused' => GooglePlaySubscriptionPaused::class,
    ];

    public function __construct(
        private GooglePlayVerificationService $verificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GooglePlayRtdnHandled $event): void
    {
        $rtdn = $event->rtdn;

        try {
            // Wrap verification and processing in transaction for data consistency
            DB::transaction(function () use ($rtdn) {
                $verified = $this->verifyNotification($rtdn);

                if ($verified) {
                    $rtdn->markAsProcessed();
                    $this->logSuccess('Google Play RTDN verified and processed', $rtdn);
                    $this->dispatchSpecificEvent($rtdn);
                } else {
                    $rtdn->markAsFailed(['error' => 'Verification failed']);
                    $this->logWarning('Google Play RTDN verification failed', $rtdn);
                }
            });
        } catch (\Exception $e) {
            $rtdn->markAsFailed(['error' => $e->getMessage()]);
            $this->logError('Google Play RTDN processing error', $rtdn, $e);
        }
    }

    /**
     * Verify the notification based on its type.
     */
    private function verifyNotification($rtdn): bool
    {
        // If already verified, still need to save the subscription/purchase data
        if ($rtdn->google_api_verified) {
            $this->logInfo('Google Play RTDN already verified, saving data from existing response', $rtdn);

            if ($rtdn->isSubscriptionNotification()) {
                // Save subscription data using existing google_api_response
                $this->verificationService->saveSubscriptionFromExistingData($rtdn);

                return true;
            }

            if ($rtdn->isOneTimeProductNotification()) {
                // Save purchase data using existing google_api_response
                $this->verificationService->savePurchaseFromExistingData($rtdn);

                return true;
            }

            return true;
        }

        if ($rtdn->isSubscriptionNotification()) {
            return $this->verificationService->verifyAndSaveSubscription($rtdn);
        }

        if ($rtdn->isOneTimeProductNotification()) {
            return $this->verificationService->verifyAndSavePurchase($rtdn);
        }

        return false;
    }

    /**
     * Dispatch specific event based on notification type.
     */
    private function dispatchSpecificEvent($rtdn): void
    {
        if (! $rtdn->user) {
            $this->logInfo("Google Play {$rtdn->notification_type} notification verified without user", $rtdn);

            return;
        }

        $notificationType = $this->determineNotificationType($rtdn);

        if (! $notificationType) {
            $this->logWarning('Unknown Google Play notification type after verification', $rtdn);

            return;
        }

        $this->handleNotification($notificationType['type'], $notificationType['enum'], $rtdn);
    }

    /**
     * Determine the notification type and its enum.
     */
    private function determineNotificationType($rtdn): ?array
    {
        // Check subscription notifications
        $subscriptionEnum = GooglePlaySubscriptionNotification::fromString($rtdn->notification_type);
        if ($subscriptionEnum) {
            return ['type' => 'subscription', 'enum' => $subscriptionEnum];
        }

        // Check product notifications
        $productEnum = GooglePlayProductNotification::fromString($rtdn->notification_type);
        if ($productEnum) {
            return ['type' => 'product', 'enum' => $productEnum];
        }

        // Check void notifications
        if (GooglePlayVoidNotification::isVoidNotification($rtdn->notification_type)) {
            return ['type' => 'void', 'enum' => null];
        }

        return null;
    }

    /**
     * Handle the notification based on its type.
     */
    private function handleNotification(string $type, $enum, $rtdn): void
    {
        match ($type) {
            'subscription' => $this->handleSubscriptionNotification($enum, $rtdn),
            'product' => $this->handleProductNotification($enum, $rtdn),
            'void' => $this->handleVoidNotification($rtdn),
            default => null,
        };
    }

    /**
     * Handle subscription notifications and dispatch appropriate events.
     */
    private function handleSubscriptionNotification(GooglePlaySubscriptionNotification $enum, $rtdn): void
    {
        // Check for plan change (purchased notification with linkedPurchaseToken)
        if ($enum->isPurchase() && $rtdn->google_api_response && isset($rtdn->google_api_response['linkedPurchaseToken'])) {
            event(new GooglePlaySubscriptionPlanChanged(
                $rtdn,
                $rtdn->user,
                $rtdn->google_api_response['linkedPurchaseToken']
            ));

            return;
        }

        // Check if this notification type should dispatch an event
        foreach (self::SUBSCRIPTION_EVENT_MAP as $method => $eventClass) {
            if ($enum->$method()) {
                event(new $eventClass($rtdn, $rtdn->user));

                return;
            }
        }

        // Log other notification types (paused, on hold, etc.)
        $this->logNotificationVerified($enum, $rtdn);
    }

    /**
     * Handle product notifications.
     */
    private function handleProductNotification(GooglePlayProductNotification $enum, $rtdn): void
    {
        $this->logNotificationVerified($enum, $rtdn);

        // Dispatch product purchase event
        if ($enum->isPurchase()) {
            event(new GooglePlayProductPurchased($rtdn, $rtdn->user));
        }
    }

    /**
     * Handle void notifications.
     */
    private function handleVoidNotification($rtdn): void
    {
        $this->logNotificationVerified(null, $rtdn, 'Purchase Voided');

        // TODO: Dispatch void event when ready
        // event(new GooglePlayPurchaseVoided($rtdn, $rtdn->user));
    }

    /**
     * Log that a notification was verified.
     */
    private function logNotificationVerified($enum, $rtdn, string $prefix = ''): void
    {
        $label = $enum ? $enum->label() : $prefix;
        $message = $prefix ? "Google Play {$prefix} notification verified" : "Google Play {$label} notification verified";

        $this->logInfo($message, $rtdn);
    }

    /**
     * Get common log context for RTDN.
     */
    private function getLogContext($rtdn, array $additionalContext = []): array
    {
        $baseContext = [
            'rtdn_id' => $rtdn->id,
            'purchase_token' => $rtdn->purchase_token,
        ];

        if ($rtdn->user_id) {
            $baseContext['user_id'] = $rtdn->user_id;
        }

        if ($rtdn->notification_type) {
            $baseContext['notification_type'] = $rtdn->notification_type;
        }

        return array_merge($baseContext, $additionalContext);
    }

    /**
     * Log info message with RTDN context.
     */
    private function logInfo(string $message, $rtdn, array $context = []): void
    {
        Log::info($message, $this->getLogContext($rtdn, $context));
    }

    /**
     * Log success message with RTDN context.
     */
    private function logSuccess(string $message, $rtdn, array $context = []): void
    {
        $this->logInfo($message, $rtdn, $context);
    }

    /**
     * Log warning message with RTDN context.
     */
    private function logWarning(string $message, $rtdn, array $context = []): void
    {
        Log::warning($message, $this->getLogContext($rtdn, $context));
    }

    /**
     * Log error message with RTDN context and exception.
     */
    private function logError(string $message, $rtdn, \Exception $e): void
    {
        Log::error($message, $this->getLogContext($rtdn, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]));
    }
}
