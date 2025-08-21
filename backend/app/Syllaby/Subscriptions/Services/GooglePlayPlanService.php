<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\Contracts\GooglePlayProductServiceInterface;
use App\Syllaby\Subscriptions\Contracts\GooglePlaySubscriptionServiceInterface;

/**
 * Google Play Plan Service orchestrator that delegates to specific services
 */
class GooglePlayPlanService
{
    public function __construct(
        private GooglePlayProductServiceInterface $productService,
        private GooglePlaySubscriptionServiceInterface $subscriptionService
    ) {}

    public function syncPlan(Plan $plan, bool $force = false, bool $isTest = false, bool $isFake = false): array
    {
        try {
            if (! $force && ! $plan->needsGooglePlaySync()) {
                return [
                    'success' => false,
                    'message' => 'Plan does not need sync',
                    'plan_id' => $plan->id,
                    'google_play_sku' => $plan->google_play_sku,
                ];
            }

            $service = $this->getServiceForPlan($plan);
            $result = $service->sync($plan, $force, $isTest, $isFake);

            return [
                'success' => $result['success'] ?? false,
                'message' => $result['success'] ? 'Plan synced successfully' : ($result['error'] ?? 'Unknown error'),
                'google_play_sku' => $plan->google_play_sku,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'data' => $result,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
            ];
        }
    }

    public function syncAllPlans(
        bool $async = false,
        bool $isTest = false,
        bool $isFake = false,
        bool $useBatch = true,
        bool $onlySubscriptions = false,
        bool $force = false
    ): array {
        if ($onlySubscriptions) {
            return $this->subscriptionService->syncAll($isTest, $isFake, $force);
        }

        $productResults = $this->productService->syncAll($isTest, $isFake);
        $subscriptionResults = $this->subscriptionService->syncAll($isTest, $isFake, $force);

        return $this->combineResults($productResults, $subscriptionResults);
    }

    public function syncOnlyProducts(bool $isFake = false): array
    {
        return $this->productService->syncAll(false, $isFake);
    }

    private function getServiceForPlan(Plan $plan): GooglePlayProductServiceInterface|GooglePlaySubscriptionServiceInterface
    {
        return in_array($plan->type, ['month', 'year'])
            ? $this->subscriptionService
            : $this->productService;
    }

    private function combineResults(array $productResults, array $subscriptionResults): array
    {
        return [
            'total' => $productResults['total'] + $subscriptionResults['total'],
            'processed' => $productResults['processed'] + $subscriptionResults['processed'],
            'successful' => $productResults['successful'] + $subscriptionResults['successful'],
            'failed' => $productResults['failed'] + $subscriptionResults['failed'],
            'errors' => array_merge($productResults['errors'] ?? [], $subscriptionResults['errors'] ?? []),
        ];
    }
}
