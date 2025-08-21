<?php

namespace App\Syllaby\Subscriptions\Services;

use App\Syllaby\Subscriptions\Enums\GooglePlayVoidRefundType;
use App\Syllaby\Subscriptions\Enums\GooglePlayVoidProductType;
use App\Syllaby\Subscriptions\Enums\GooglePlayVoidNotification;
use App\Syllaby\Subscriptions\Enums\GooglePlayProductNotification;
use App\Syllaby\Subscriptions\Enums\GooglePlaySubscriptionNotification;

/**
 * Google Play RTDN Mapper Service
 *
 * Maps incoming Google Play RTDN webhook data to standardized notification types
 * using the new enum system for better type safety and consistency.
 */
class GooglePlayRtdnMapper
{
    /**
     * Map a subscription notification from Google Play RTDN to string format.
     */
    public static function mapSubscriptionNotification(int $notificationType): ?string
    {
        $enum = GooglePlaySubscriptionNotification::tryFrom($notificationType);

        return $enum?->toString();
    }

    /**
     * Map a one-time product notification from Google Play RTDN to string format.
     */
    public static function mapProductNotification(int $notificationType): ?string
    {
        $enum = GooglePlayProductNotification::tryFrom($notificationType);

        return $enum?->toString();
    }

    /**
     * Map voided purchase notification - always returns the same string.
     */
    public static function mapVoidedPurchaseNotification(): string
    {
        return GooglePlayVoidNotification::getNotificationType();
    }

    /**
     * Map a product type for voided purchases.
     */
    public static function mapVoidedProductType(int $productType): ?GooglePlayVoidProductType
    {
        return GooglePlayVoidProductType::tryFrom($productType);
    }

    /**
     * Map a refund type for voided purchases.
     */
    public static function mapVoidedRefundType(int $refundType): ?GooglePlayVoidRefundType
    {
        return GooglePlayVoidRefundType::tryFrom($refundType);
    }

    /**
     * Determine notification category from RTDN data.
     *
     * @param  array  $rtdnData  The decoded RTDN message data
     */
    public static function determineNotificationType(array $rtdnData): ?string
    {
        // Check for subscription notification
        if (isset($rtdnData['subscriptionNotification'])) {
            $notificationType = $rtdnData['subscriptionNotification']['notificationType'] ?? null;
            if ($notificationType !== null) {
                return self::mapSubscriptionNotification((int) $notificationType);
            }
        }

        // Check for one-time product notification
        if (isset($rtdnData['oneTimeProductNotification'])) {
            $notificationType = $rtdnData['oneTimeProductNotification']['notificationType'] ?? null;
            if ($notificationType !== null) {
                return self::mapProductNotification((int) $notificationType);
            }
        }

        // Check for voided purchase notification
        if (isset($rtdnData['voidedPurchaseNotification'])) {
            return self::mapVoidedPurchaseNotification();
        }

        return null;
    }

    /**
     * Extract purchase token from RTDN data.
     */
    public static function extractPurchaseToken(array $rtdnData): ?string
    {
        // Check subscription notification
        if (isset($rtdnData['subscriptionNotification']['purchaseToken'])) {
            return $rtdnData['subscriptionNotification']['purchaseToken'];
        }

        // Check one-time product notification
        if (isset($rtdnData['oneTimeProductNotification']['purchaseToken'])) {
            return $rtdnData['oneTimeProductNotification']['purchaseToken'];
        }

        // Check voided purchase notification
        if (isset($rtdnData['voidedPurchaseNotification']['purchaseToken'])) {
            return $rtdnData['voidedPurchaseNotification']['purchaseToken'];
        }

        return null;
    }

    /**
     * Extract SKU/Product ID from RTDN data.
     */
    public static function extractSku(array $rtdnData): ?string
    {
        // Check subscription notification
        if (isset($rtdnData['subscriptionNotification']['subscriptionId'])) {
            return $rtdnData['subscriptionNotification']['subscriptionId'];
        }

        // Check one-time product notification
        if (isset($rtdnData['oneTimeProductNotification']['sku'])) {
            return $rtdnData['oneTimeProductNotification']['sku'];
        }

        return null;
    }

    /**
     * Get human-readable label for notification type string.
     */
    public static function getNotificationLabel(string $notificationType): string
    {
        // Try subscription notification
        $subscriptionEnum = GooglePlaySubscriptionNotification::fromString($notificationType);
        if ($subscriptionEnum) {
            return $subscriptionEnum->label();
        }

        // Try product notification
        $productEnum = GooglePlayProductNotification::fromString($notificationType);
        if ($productEnum) {
            return $productEnum->label();
        }

        // Check voided purchase
        if (GooglePlayVoidNotification::isVoidNotification($notificationType)) {
            return GooglePlayVoidNotification::getLabel();
        }

        return 'Unknown Notification';
    }

    /**
     * Check if notification type indicates a purchase event.
     */
    public static function isPurchaseEvent(string $notificationType): bool
    {
        $subscriptionEnum = GooglePlaySubscriptionNotification::fromString($notificationType);
        if ($subscriptionEnum) {
            return $subscriptionEnum->isPurchase();
        }

        $productEnum = GooglePlayProductNotification::fromString($notificationType);
        if ($productEnum) {
            return $productEnum->isPurchase();
        }

        return false;
    }

    /**
     * Check if notification type indicates a cancellation event.
     */
    public static function isCancellationEvent(string $notificationType): bool
    {
        $subscriptionEnum = GooglePlaySubscriptionNotification::fromString($notificationType);
        if ($subscriptionEnum) {
            return $subscriptionEnum->isCancellation();
        }

        $productEnum = GooglePlayProductNotification::fromString($notificationType);
        if ($productEnum) {
            return $productEnum->isCancellation();
        }

        return GooglePlayVoidNotification::isVoidNotification($notificationType);
    }

    /**
     * Get all purchase event notification types.
     */
    public static function getAllPurchaseTypes(): array
    {
        return array_merge(
            GooglePlaySubscriptionNotification::getPurchaseStrings(),
            GooglePlayProductNotification::getPurchaseStrings()
        );
    }

    /**
     * Get all cancellation event notification types.
     */
    public static function getAllCancellationTypes(): array
    {
        return array_merge(
            GooglePlaySubscriptionNotification::getCancellationStrings(),
            GooglePlayProductNotification::getCancellationStrings(),
            GooglePlayVoidNotification::getCancellationStrings()
        );
    }

    /**
     * Get all valid notification types.
     */
    public static function getAllValidTypes(): array
    {
        return array_merge(
            GooglePlaySubscriptionNotification::getAllStrings(),
            GooglePlayProductNotification::getAllStrings(),
            GooglePlayVoidNotification::getCancellationStrings()
        );
    }
}
