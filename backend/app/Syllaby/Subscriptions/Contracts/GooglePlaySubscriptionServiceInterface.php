<?php

namespace App\Syllaby\Subscriptions\Contracts;

use App\Syllaby\Subscriptions\Plan;

interface GooglePlaySubscriptionServiceInterface extends GooglePlayServiceInterface
{
    public function generateBasePlanId(Plan $plan): string;

    public function fetchAllSubscriptions(): array;

    public function getSubscription(string $productId): array;

    public function deleteSubscription(string $productId): array;

    public function deactivateSubscriptionBasePlan(string $productId, string $basePlanId): array;
    
    /**
     * Verify a subscription purchase token with Google Play API.
     *
     * @param string $purchaseToken The purchase token to verify
     * @param string $productId The subscription product ID
     * @return array{verified: bool, data: array|null, error: string|null}
     */
    public function verifyPurchaseToken(string $purchaseToken, string $productId): array;
    
    /**
     * Map Google Play subscription status based on API data.
     *
     * @param array $apiData The API response data
     * @param string|null $notificationType Optional notification type for context
     * @return string The mapped status
     */
    public function mapSubscriptionStatus(array $apiData, ?string $notificationType = null): string;
}
