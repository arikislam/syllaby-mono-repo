<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use App\Syllaby\Subscriptions\GooglePlaySubscription;
use App\Syllaby\Subscriptions\Events\GooglePlayRtdnReceived;
use App\Syllaby\Subscriptions\Enums\GooglePlayVoidNotification;
use App\Syllaby\Subscriptions\Enums\GooglePlayProductNotification;
use App\Syllaby\Subscriptions\Enums\GooglePlaySubscriptionNotification;

class GooglePlayRtdnController extends Controller
{
    public function handle(Request $request)
    {
        $raw = $request->getContent();
        $payload = json_decode($raw, true) ?: $request->all();

        // Log with dedicated RTDN channel if configured
        $channel = config('google-play.logging.rtdn_channel', config('logging.default'));
        Log::channel($channel)->debug('Google Play RTDN received', [
            'payload' => $payload,
        ]);

        // Extract purchase token and notification type for validation
        $purchaseToken = $this->extractPurchaseToken($payload);
        $notificationType = $this->extractNotificationType($payload);

        if ($purchaseToken && $notificationType) {
            // Validate notification consistency before processing
            $validationResult = $this->validateNotificationConsistency($purchaseToken, $notificationType, $payload);

            if (! $validationResult['valid']) {
                Log::channel($channel)->warning('Notification consistency validation failed', [
                    'purchase_token' => $purchaseToken,
                    'notification_type' => $notificationType,
                    'reason' => $validationResult['reason'],
                    'message_id' => $payload['messageId'] ?? null,
                ]);

                // Still acknowledge the webhook but don't process it
                return response()->noContent();
            }

            // Check if we already have an RTDN record with user association from checkout
            $existingRtdn = GooglePlayRtdn::where('purchase_token', $purchaseToken)
                ->whereNotNull('user_id')
                ->first();

            if ($existingRtdn) {
                Log::channel($channel)->info('Found existing RTDN with user association', [
                    'rtdn_id' => $existingRtdn->id,
                    'user_id' => $existingRtdn->user_id,
                    'purchase_token' => $purchaseToken,
                    'notification_type' => $notificationType,
                ]);

                // Update the existing RTDN with webhook data
                $existingRtdn->update([
                    'message_id' => $payload['messageId'] ?? $existingRtdn->message_id,
                    'notification_type' => $notificationType,
                    'rtdn_response' => array_merge($existingRtdn->rtdn_response ?? [], [
                        'webhook_payload' => $payload,
                        'webhook_received_at' => now()->toIso8601String(),
                    ]),
                ]);
            } else {
                // Log when we receive webhooks without prior user association
                if ($this->isInitialPurchaseNotification($notificationType)) {
                    Log::channel($channel)->warning('Received initial purchase webhook without user association', [
                        'purchase_token' => $purchaseToken,
                        'notification_type' => $notificationType,
                        'message_id' => $payload['messageId'] ?? null,
                        'recommendation' => 'Ensure users initiate purchases through app first',
                    ]);
                }
            }
        }

        // Dispatch the RTDN received event
        event(new GooglePlayRtdnReceived($payload));

        // Immediately ACK to Google Play with 204 No Content
        // This tells Google Play we successfully received the notification
        return response()->noContent();
    }

    /**
     * Extract purchase token from payload.
     */
    private function extractPurchaseToken(array $payload): ?string
    {
        $paths = [
            ['subscriptionNotification', 'purchaseToken'],
            ['oneTimeProductNotification', 'purchaseToken'],
            ['voidedPurchaseNotification', 'purchaseToken'],
        ];

        foreach ($paths as $path) {
            $value = $this->getNestedValue($payload, $path);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extract notification type from payload.
     */
    private function extractNotificationType(array $payload): ?string
    {
        // Check for subscription notification
        if (isset($payload['subscriptionNotification']['notificationType'])) {
            $type = $payload['subscriptionNotification']['notificationType'];

            return $this->mapSubscriptionNotificationType($type);
        }

        // Check for one-time product notification
        if (isset($payload['oneTimeProductNotification']['notificationType'])) {
            $type = $payload['oneTimeProductNotification']['notificationType'];

            return $this->mapProductNotificationType($type);
        }

        // Check for voided purchase notification
        if (isset($payload['voidedPurchaseNotification'])) {
            return GooglePlayVoidNotification::getNotificationType();
        }

        return null;
    }

    /**
     * Map subscription notification type to string using enum.
     */
    private function mapSubscriptionNotificationType(int $type): string
    {
        $enum = GooglePlaySubscriptionNotification::tryFrom($type);

        return $enum ? $enum->toString() : 'subscription.unknown';
    }

    /**
     * Map product notification type to string using enum.
     */
    private function mapProductNotificationType(int $type): string
    {
        $enum = GooglePlayProductNotification::tryFrom($type);

        return $enum ? $enum->toString() : 'product.unknown';
    }

    /**
     * Get nested value from array.
     */
    private function getNestedValue(array $array, array $keys)
    {
        $value = $array;
        foreach ($keys as $key) {
            if (! is_array($value) || ! array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Check if notification type indicates an initial purchase.
     */
    private function isInitialPurchaseNotification(?string $notificationType): bool
    {
        if (! $notificationType) {
            return false;
        }

        // Use enum methods to check for purchase notifications
        $subscriptionEnum = GooglePlaySubscriptionNotification::fromString($notificationType);
        if ($subscriptionEnum && $subscriptionEnum->isPurchase()) {
            return true;
        }

        $productEnum = GooglePlayProductNotification::fromString($notificationType);
        if ($productEnum && $productEnum->isPurchase()) {
            return true;
        }

        return false;
    }

    /**
     * Validate notification consistency to ensure proper event ordering.
     */
    private function validateNotificationConsistency(string $purchaseToken, string $notificationType, array $payload): array
    {
        // Get notification enum for validation
        $subscriptionEnum = GooglePlaySubscriptionNotification::fromString($notificationType);
        $productEnum = GooglePlayProductNotification::fromString($notificationType);
        $isVoidNotification = GooglePlayVoidNotification::isVoidNotification($notificationType);

        // Check if we have any existing records for this purchase token
        $existingRtdn = GooglePlayRtdn::where('purchase_token', $purchaseToken)->first();
        $existingSubscription = GooglePlaySubscription::where('purchase_token', $purchaseToken)->first();

        // Validation rules based on notification type
        if ($subscriptionEnum) {
            return $this->validateSubscriptionNotification($subscriptionEnum, $existingRtdn, $existingSubscription, $payload);
        }

        if ($productEnum) {
            return $this->validateProductNotification($productEnum, $existingRtdn, $payload);
        }

        if ($isVoidNotification) {
            return $this->validateVoidNotification($existingRtdn, $existingSubscription, $payload);
        }

        return ['valid' => false, 'reason' => 'Unknown notification type'];
    }

    /**
     * Validate subscription notification consistency.
     */
    private function validateSubscriptionNotification(
        GooglePlaySubscriptionNotification $enum,
        ?GooglePlayRtdn $existingRtdn,
        ?GooglePlaySubscription $existingSubscription,
        array $payload
    ): array {
        // Purchase notifications should be the first event
        if ($enum->isPurchase()) {
            // Allow purchase if no existing records or if existing RTDN is from checkout (pending verification)
            if (! $existingRtdn || ($existingRtdn && ! $existingRtdn->google_api_verified)) {
                return ['valid' => true, 'reason' => 'Valid initial purchase'];
            }

            // If we already have a verified purchase, this might be a duplicate
            return ['valid' => false, 'reason' => 'Purchase already exists and verified'];
        }

        // Cancellation/expiration notifications require an existing subscription
        if ($enum->isCancellation()) {
            if (! $existingSubscription && ! $existingRtdn) {
                return ['valid' => false, 'reason' => 'Cannot cancel non-existent subscription'];
            }

            // Check if subscription is already canceled
            if ($existingSubscription && in_array($existingSubscription->status, ['canceled', 'expired'])) {
                return ['valid' => false, 'reason' => 'Subscription already canceled/expired'];
            }

            return ['valid' => true, 'reason' => 'Valid cancellation'];
        }

        // Renewal notifications require an existing subscription
        if ($enum->isRenewal()) {
            if (! $existingSubscription && ! $existingRtdn) {
                return ['valid' => false, 'reason' => 'Cannot renew non-existent subscription'];
            }

            return ['valid' => true, 'reason' => 'Valid renewal'];
        }

        // Pause/hold notifications require an existing active subscription
        if ($enum->isPaused()) {
            if (! $existingSubscription) {
                return ['valid' => false, 'reason' => 'Cannot pause non-existent subscription'];
            }

            if ($existingSubscription->status === 'canceled') {
                return ['valid' => false, 'reason' => 'Cannot pause canceled subscription'];
            }

            return ['valid' => true, 'reason' => 'Valid pause/hold'];
        }

        // Other notifications (price changes, deferrals, etc.) require existing subscription
        if (! $existingSubscription && ! $existingRtdn) {
            return ['valid' => false, 'reason' => 'Notification requires existing subscription'];
        }

        return ['valid' => true, 'reason' => 'Valid notification'];
    }

    /**
     * Validate product notification consistency.
     */
    private function validateProductNotification(
        GooglePlayProductNotification $enum,
        ?GooglePlayRtdn $existingRtdn,
        array $payload
    ): array {
        // Purchase notifications should be the first event
        if ($enum->isPurchase()) {
            if (! $existingRtdn || ($existingRtdn && ! $existingRtdn->google_api_verified)) {
                return ['valid' => true, 'reason' => 'Valid product purchase'];
            }

            return ['valid' => false, 'reason' => 'Product already purchased and verified'];
        }

        // Cancellation requires existing purchase
        if ($enum->isCancellation()) {
            if (! $existingRtdn) {
                return ['valid' => false, 'reason' => 'Cannot cancel non-existent product purchase'];
            }

            return ['valid' => true, 'reason' => 'Valid product cancellation'];
        }

        return ['valid' => true, 'reason' => 'Valid product notification'];
    }

    /**
     * Validate void notification consistency.
     */
    private function validateVoidNotification(
        ?GooglePlayRtdn $existingRtdn,
        ?GooglePlaySubscription $existingSubscription,
        array $payload
    ): array {
        // Void notifications require an existing purchase or subscription
        if (! $existingRtdn && ! $existingSubscription) {
            return ['valid' => false, 'reason' => 'Cannot void non-existent purchase'];
        }

        return ['valid' => true, 'reason' => 'Valid void notification'];
    }
}
