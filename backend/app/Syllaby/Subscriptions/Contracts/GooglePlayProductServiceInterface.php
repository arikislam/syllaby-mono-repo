<?php

namespace App\Syllaby\Subscriptions\Contracts;

use App\Syllaby\Subscriptions\Plan;

interface GooglePlayProductServiceInterface extends GooglePlayServiceInterface
{
    public function generateProductSku(Plan $plan): string;

    public function fetchAllProducts(): array;

    public function deleteProduct(string $sku): array;
    
    /**
     * Verify a product purchase token with Google Play API.
     *
     * @param string $purchaseToken The purchase token to verify
     * @param string $productId The product SKU
     * @return array{verified: bool, data: array|null, error: string|null}
     */
    public function verifyPurchaseToken(string $purchaseToken, string $productId): array;
    
    /**
     * Map Google Play purchase status based on API data.
     *
     * @param array $apiData The API response data
     * @return string The mapped status
     */
    public function mapPurchaseStatus(array $apiData): string;
}
