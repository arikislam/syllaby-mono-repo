<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class SubscriptionService
{
    /**
     * Gets all available products.
     *
     * @throws Exception
     */
    public function getAllProducts(): array
    {
        try {
            $products = Cashier::stripe()->products->all(['limit' => 70, 'active' => true]);

            return collect($products->data)->map(fn ($product) => $product->toArray())->toArray();
        } catch (ApiErrorException $e) {
            Log::error($e->getMessage());
            throw new Exception("Subscription api exception: {$e->getMessage()}");
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new Exception("Error with subscription client: {$e->getMessage()}");
        }
    }

    /**
     * Gets all available prices.
     *
     * @throws Exception
     */
    public function getAllPrices(string $productId): array
    {
        try {
            $prices = Cashier::stripe()->prices->all(['limit' => 40, 'product' => $productId]);

            return collect($prices->data)->map(fn ($price) => $price->toArray())->toArray();
        } catch (ApiErrorException $e) {
            Log::error($e->getMessage());
            throw new Exception("Subscription api exception: {$e->getMessage()}");
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new Exception("Error with subscription client: {$e->getMessage()}");
        }
    }

    /**
     * Gets all available coupons.
     *
     * @throws Exception
     */
    public function getAllCoupons(): array
    {
        try {
            $coupons = Cashier::stripe()->coupons->all(['limit' => 50]);

            return collect($coupons->data)->map(fn ($coupon) => $coupon->toArray())->toArray();
        } catch (ApiErrorException $exception) {
            throw new Exception('Subscription api exception', ['reason' => $exception->getMessage()]);
        } catch (Exception $exception) {
            throw new Exception('Error with subscription client', ['reason' => $exception->getMessage()]);
        }
    }
}
