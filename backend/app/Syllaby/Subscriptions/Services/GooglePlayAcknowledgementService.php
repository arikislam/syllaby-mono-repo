<?php

namespace App\Syllaby\Subscriptions\Services;

use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use App\Syllaby\Subscriptions\GooglePlayPurchase;
use App\Syllaby\Subscriptions\GooglePlaySubscription;
use App\Syllaby\Subscriptions\Contracts\GooglePlayApiClientInterface;

class GooglePlayAcknowledgementService extends GooglePlayBaseService
{
    /**
     * Purchase types supported by Google Play.
     */
    private const TYPE_SUBSCRIPTION = 'subscription';
    private const TYPE_PRODUCT = 'product';

    /**
     * Acknowledgement states from Google Play API.
     */
    private const ACKNOWLEDGEMENT_STATE_PENDING = 0;
    private const ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED = 1;

    public function __construct(
        private GooglePlayApiClientInterface $googlePlayClient
    ) {}

    /**
     * Check if subscription should be acknowledged.
     */
    public function shouldAcknowledgeSubscription(array $apiData): bool
    {
        return ($apiData['acknowledgementState'] ?? self::ACKNOWLEDGEMENT_STATE_PENDING) === self::ACKNOWLEDGEMENT_STATE_PENDING;
    }

    /**
     * Check if purchase should be acknowledged.
     */
    public function shouldAcknowledgePurchase(array $apiData): bool
    {
        return ($apiData['acknowledgementState'] ?? self::ACKNOWLEDGEMENT_STATE_PENDING) === self::ACKNOWLEDGEMENT_STATE_PENDING;
    }

    /**
     * Acknowledge a purchase or subscription.
     */
    public function acknowledgePurchase(string $purchaseToken, string $productId, string $type = self::TYPE_SUBSCRIPTION): bool
    {
        try {
            $endpoint = $this->buildAcknowledgementEndpoint($productId, $purchaseToken, $type);
            $result = $this->makeApiRequest('post', $endpoint, [], [
                'purchase_token' => $purchaseToken,
                'product_id' => $productId,
                'type' => $type,
            ]);

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            return $this->handleAcknowledgementException($e, $purchaseToken, $productId, $type);
        }
    }

    /**
     * Acknowledge subscription if needed and update the record.
     */
    public function acknowledgeSubscriptionIfNeeded(
        GooglePlayRtdn $rtdn, 
        GooglePlaySubscription $subscription, 
        array $apiData,
        bool $isRenewal = false
    ): bool {
        if (!$this->shouldAcknowledgeSubscription($apiData) || $isRenewal) {
            return false;
        }

        if (!$this->hasRequiredDataForAcknowledgement($rtdn)) {
            $this->logWarning('Missing required data for subscription acknowledgement', [
                'rtdn_id' => $rtdn->id,
                'has_purchase_token' => !empty($rtdn->purchase_token),
                'has_product_id' => !empty($rtdn->plan->googlePlayPlan->product_id ?? null),
            ]);
            return false;
        }

        $acknowledged = $this->acknowledgePurchase(
            $rtdn->purchase_token,
            $rtdn->plan->googlePlayPlan->product_id,
            self::TYPE_SUBSCRIPTION
        );
        
        if ($acknowledged) {
            $this->updateSubscriptionAcknowledgement($subscription);
            $this->logSuccessfulAcknowledgement('subscription', $subscription->id, $rtdn->purchase_token);
            return true;
        }

        $this->logFailedAcknowledgement('subscription', $subscription->id, $rtdn->purchase_token);
        return false;
    }

    /**
     * Acknowledge one-time purchase if needed and update the record.
     */
    public function acknowledgePurchaseIfNeeded(
        GooglePlayRtdn $rtdn,
        GooglePlayPurchase $purchase,
        array $apiData
    ): bool {
        if (!$this->shouldAcknowledgePurchase($apiData)) {
            return false;
        }

        if (!$this->hasRequiredDataForAcknowledgement($rtdn)) {
            $this->logWarning('Missing required data for purchase acknowledgement', [
                'rtdn_id' => $rtdn->id,
                'has_purchase_token' => !empty($rtdn->purchase_token),
                'has_product_id' => !empty($rtdn->plan->googlePlayPlan->product_id ?? null),
            ]);
            return false;
        }

        $acknowledged = $this->acknowledgePurchase(
            $rtdn->purchase_token,
            $rtdn->plan->googlePlayPlan->product_id,
            self::TYPE_PRODUCT
        );

        if ($acknowledged) {
            $this->updatePurchaseAcknowledgement($purchase);
            $this->logSuccessfulAcknowledgement('purchase', $purchase->id, $rtdn->purchase_token);
            return true;
        }

        $this->logFailedAcknowledgement('purchase', $purchase->id, $rtdn->purchase_token);
        return false;
    }

    /**
     * Update subscription acknowledgement status.
     */
    public function updateSubscriptionAcknowledgement(GooglePlaySubscription $subscription): void
    {
        $subscription->update(['acknowledgement_state' => self::ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED]);
        
        $this->logDebug('Subscription acknowledgement state updated', [
            'subscription_id' => $subscription->id,
            'purchase_token' => $subscription->purchase_token,
        ]);
    }

    /**
     * Update purchase acknowledgement status.
     */
    public function updatePurchaseAcknowledgement(GooglePlayPurchase $purchase, array $apiData = []): void
    {
        // If API data shows it's already acknowledged, use the acknowledge method
        if ($this->isAlreadyAcknowledged($apiData)) {
            $purchase->acknowledge();
            $this->logDebug('Purchase already acknowledged according to API', [
                'purchase_id' => $purchase->id,
            ]);
            return;
        }

        // Otherwise, just update the acknowledgement state
        $purchase->update(['acknowledgement_state' => self::ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED]);
        
        $this->logDebug('Purchase acknowledgement state updated', [
            'purchase_id' => $purchase->id,
            'purchase_token' => $purchase->purchase_token,
        ]);
    }

    /**
     * Check if RTDN has required data for acknowledgement.
     */
    private function hasRequiredDataForAcknowledgement(GooglePlayRtdn $rtdn): bool
    {
        return $rtdn->purchase_token && 
               $rtdn->plan && 
               $rtdn->plan->googlePlayPlan && 
               $rtdn->plan->googlePlayPlan->product_id;
    }

    /**
     * Check if purchase is already acknowledged according to API data.
     */
    private function isAlreadyAcknowledged(array $apiData): bool
    {
        return !empty($apiData) && 
               isset($apiData['acknowledgementState']) && 
               $apiData['acknowledgementState'] === self::ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED;
    }

    /**
     * Build acknowledgement endpoint.
     */
    private function buildAcknowledgementEndpoint(string $productId, string $purchaseToken, string $type): string
    {
        return $type === self::TYPE_SUBSCRIPTION
            ? "/purchases/subscriptions/{$productId}/tokens/{$purchaseToken}:acknowledge"
            : "/purchases/products/{$productId}/tokens/{$purchaseToken}:acknowledge";
    }

    /**
     * Handle acknowledgement exception.
     */
    private function handleAcknowledgementException(\Exception $e, string $purchaseToken, string $productId, string $type): bool
    {
        $this->handleApiException($e, 'Failed to acknowledge Google Play purchase', [
            'purchase_token' => $purchaseToken,
            'product_id' => $productId,
            'type' => $type,
        ]);

        return false;
    }

    /**
     * Log successful acknowledgement.
     */
    private function logSuccessfulAcknowledgement(string $type, int $id, string $purchaseToken): void
    {
        $this->logInfo("Google Play {$type} acknowledged successfully", [
            "{$type}_id" => $id,
            'purchase_token' => $purchaseToken,
        ]);
    }

    /**
     * Log failed acknowledgement.
     */
    private function logFailedAcknowledgement(string $type, int $id, string $purchaseToken): void
    {
        $this->logWarning("Failed to acknowledge Google Play {$type}", [
            "{$type}_id" => $id,
            'purchase_token' => $purchaseToken,
        ]);
    }
} 