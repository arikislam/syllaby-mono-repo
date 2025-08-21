<?php

namespace App\Syllaby\Subscriptions\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use App\Syllaby\Subscriptions\Services\GooglePlayProductService;
use App\Syllaby\Subscriptions\Services\GooglePlaySubscriptionService;

class CheckGooglePlayStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-play:status
                          {--products : Check only products}
                          {--subscriptions : Check only subscriptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare Google Play products with database records';

    /**
     * Execute the console command.
     */
    public function handle(
        GooglePlayProductService $productService,
        GooglePlaySubscriptionService $subscriptionService
    ) {
        $this->info('Starting Google Play synchronization status check...');

        try {
            $checkAll = ! $this->option('products') && ! $this->option('subscriptions');

            // Check one-time products
            if ($checkAll || $this->option('products')) {
                $this->verifyOneTimeProducts($productService);
            }

            // Check subscriptions
            if ($checkAll || $this->option('subscriptions')) {
                $this->verifySubscriptions($subscriptionService);
            }
        } catch (Exception $e) {
            $this->error("Google Play status check failed: {$e->getMessage()}");
            Log::error('Google Play status check failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }

        $this->info('Google Play synchronization status check completed.');

        return 0;
    }

    /**
     * Verify one-time products with Google Play
     */
    private function verifyOneTimeProducts(GooglePlayProductService $productService): void
    {
        $this->info("\n=== One-Time Products Sync Status ===");

        // Get products from Google Play
        $googleProducts = $productService->fetchAllProducts();
        $googleSkus = collect($googleProducts)->pluck('sku')->toArray();

        // Get products from database
        $dbProducts = Plan::whereIn('type', ['one_time', 'product'])->with('googlePlayPlan')->get();
        $dbProductsWithSku = $dbProducts->filter(function ($plan) {
            return $plan->googlePlayPlan && $plan->googlePlayPlan->product_id;
        });
        $dbSkus = $dbProductsWithSku->map(function ($plan) {
            return $plan->googlePlayPlan->product_id;
        })->toArray();

        // Display counts
        $this->displayItemCounts('products', count($googleProducts), $dbProducts->count(), $dbProductsWithSku->count());

        // Check for synced products
        $syncedSkus = array_intersect($dbSkus, $googleSkus);
        if (count($syncedSkus) > 0) {
            $this->displaySyncedProducts($syncedSkus, $dbProductsWithSku, $googleProducts);
        }

        // Check for products in DB but not in Google Play
        $missingSkus = array_diff($dbSkus, $googleSkus);
        if (count($missingSkus) > 0) {
            $this->warn("\n⚠️ ".count($missingSkus).' products in database but NOT in Google Play:');
            foreach ($missingSkus as $sku) {
                $product = $dbProductsWithSku->first(function ($plan) use ($sku) {
                    return $plan->googlePlayPlan->product_id === $sku;
                });
                if ($product) {
                    $this->line("  • {$product->name} (ID: {$product->id}) - SKU: {$sku}");
                }
            }
        }

        // Check for products in Google Play but not in DB
        $this->displayMissingGoogleItems(
            array_diff($googleSkus, $dbSkus),
            $googleProducts,
            'products in Google Play but NOT in database',
            'sku',
            function ($item) {
                if (! is_array($item)) {
                    return '  • Invalid product format';
                }

                $status = $item['status'] ?? 'unknown';
                $title = isset($item['listings']) && isset($item['listings']['en-US']) && isset($item['listings']['en-US']['title'])
                    ? $item['listings']['en-US']['title']
                    : 'Unknown Title';
                $sku = $item['sku'] ?? 'unknown';

                return "  • {$title} - SKU: {$sku}, Status: {$status}";
            }
        );

        // Check for products with no SKU
        $this->displayMissingFields(
            $dbProducts->filter(function ($plan) {
                return ! $plan->googlePlayPlan;
            }),
            'products in database without Google Play SKU',
            fn ($product) => "  • {$product->name} (ID: {$product->id})"
        );

        // Check for duplicate SKUs in database
        $this->displayDuplicates(
            'SKU',
            function ($duplicate, $items) {
                $this->line("  • SKU: {$duplicate->product_id} (used {$duplicate->count} times)");
                foreach ($items as $item) {
                    $plan = Plan::find($item->plan_id);
                    if ($plan) {
                        $this->line("    - {$plan->name} (ID: {$plan->id})");
                    }
                }
            }
        );
    }

    /**
     * Verify subscriptions with Google Play
     */
    private function verifySubscriptions(GooglePlaySubscriptionService $subscriptionService): void
    {
        $this->info("\n=== Subscription Plans Sync Status ===");

        // Get subscriptions from Google Play
        $googleSubscriptions = $subscriptionService->fetchAllSubscriptions();
        $googleProductIds = collect($googleSubscriptions)->pluck('productId')->toArray();

        // Get subscriptions from database
        $dbSubscriptions = Plan::whereIn('type', ['month', 'year'])->with('googlePlayPlan')->get();
        $dbSubscriptionsWithId = $dbSubscriptions->filter(function ($plan) {
            return $plan->googlePlayPlan && $plan->googlePlayPlan->product_id;
        });
        $dbProductIds = $dbSubscriptionsWithId->map(function ($plan) {
            return $plan->googlePlayPlan->product_id;
        })->toArray();

        // Display counts
        $this->displayItemCounts('subscription plans', count($googleSubscriptions), $dbSubscriptions->count(), $dbSubscriptionsWithId->count());

        // Check for synced subscriptions
        $syncedIds = array_intersect($dbProductIds, $googleProductIds);
        if (count($syncedIds) > 0) {
            $this->displaySyncedSubscriptions($syncedIds, $dbSubscriptionsWithId, $googleSubscriptions);
        }

        // Check for subscriptions in DB but not in Google Play
        $missingIds = array_diff($dbProductIds, $googleProductIds);
        if (count($missingIds) > 0) {
            $this->warn("\n⚠️ ".count($missingIds).' subscription plans in database but NOT in Google Play:');
            foreach ($missingIds as $productId) {
                $subscription = $dbSubscriptionsWithId->first(function ($plan) use ($productId) {
                    return $plan->googlePlayPlan->product_id === $productId;
                });
                if ($subscription) {
                    $this->line("  • {$subscription->name} (ID: {$subscription->id}) - Product ID: {$productId}");
                }
            }
        }

        // Check for subscriptions in Google Play but not in DB
        $this->displayMissingGoogleItems(
            array_diff($googleProductIds, $dbProductIds),
            $googleSubscriptions,
            'subscription plans in Google Play but NOT in database',
            'productId',
            function ($item) {
                if (! is_array($item)) {
                    return '  • Invalid subscription format';
                }

                $status = 'unknown';
                if (is_array($item['basePlans']) && ! empty($item['basePlans'])) {
                    $status = $item['basePlans'][0]['state'] ?? 'unknown';
                }

                $title = 'Unknown Title';
                if (is_array($item['listings']) && ! empty($item['listings'])) {
                    $title = $item['listings'][0]['title'] ?? 'Unknown Title';
                }

                $productId = $item['productId'] ?? 'unknown';

                return "  • {$title} - Product ID: {$productId}, Status: {$status}";
            }
        );

        // Check for subscriptions with no ID
        $this->displayMissingFields(
            $dbSubscriptions->filter(function ($plan) {
                return ! $plan->googlePlayPlan;
            }),
            'subscription plans in database without Google Play ID',
            fn ($subscription) => "  • {$subscription->name} (ID: {$subscription->id})"
        );

        // Check for duplicate IDs in database
        $this->displayDuplicates(
            'Product ID',
            function ($duplicate, $items) {
                $this->line("  • Product ID: {$duplicate->product_id} (used {$duplicate->count} times)");
                foreach ($items as $item) {
                    $plan = Plan::find($item->plan_id);
                    if ($plan) {
                        $this->line("    - {$plan->name} (ID: {$plan->id})");
                    }
                }
            }
        );
    }

    /**
     * Display counts of items
     */
    private function displayItemCounts(string $itemType, int $googleCount, int $dbCount, int $dbWithIdCount): void
    {
        $this->info("Google Play: {$googleCount} {$itemType}");
        $this->info("Database: {$dbCount} {$itemType} ({$dbWithIdCount} with IDs)");
    }

    /**
     * Display synced products
     */
    private function displaySyncedProducts(array $syncedSkus, Collection $dbProductsWithSku, array $googleProducts): void
    {
        $this->info("\n✅ ".count($syncedSkus).' products correctly synced:');
        foreach ($syncedSkus as $sku) {
            $product = $dbProductsWithSku->first(function ($plan) use ($sku) {
                return $plan->googlePlayPlan->product_id === $sku;
            });
            $googleProduct = collect($googleProducts)->firstWhere('sku', $sku);
            $status = $googleProduct['status'] ?? 'unknown';
            $price = isset($googleProduct['defaultPrice']['priceMicros']) ?
                ($googleProduct['defaultPrice']['priceMicros'] / 1000000).' '.($googleProduct['defaultPrice']['currency'] ?? '') :
                'N/A';

            $this->line("  • {$product->name} (ID: {$product->id}) - SKU: {$sku}, Status: {$status}, Price: {$price}");
        }
    }

    /**
     * Display synced subscriptions
     */
    private function displaySyncedSubscriptions(array $syncedIds, Collection $dbSubscriptionsWithId, array $googleSubscriptions): void
    {
        $this->info("\n✅ ".count($syncedIds).' subscription plans correctly synced:');
        foreach ($syncedIds as $productId) {
            $subscription = $dbSubscriptionsWithId->first(function ($plan) use ($productId) {
                return $plan->googlePlayPlan->product_id === $productId;
            });
            $googleSubscription = collect($googleSubscriptions)->firstWhere('productId', $productId);

            $period = 'unknown';
            $status = 'unknown';
            $basePlanId = 'unknown';

            if (! blank($googleSubscription['basePlans'])) {
                $basePlan = $googleSubscription['basePlans'][0];
                $status = $basePlan['state'] ?? 'unknown';
                $basePlanId = $basePlan['basePlanId'] ?? 'unknown';

                if (isset($basePlan['autoRenewingBasePlanType']['billingPeriodDuration'])) {
                    $period = $basePlan['autoRenewingBasePlanType']['billingPeriodDuration'];
                }
            }

            $this->line(
                "  • {$subscription->name} (ID: {$subscription->id}) - ".
                "Product ID: {$productId}, Status: {$status}, Period: {$period}, Base Plan: {$basePlanId}"
            );
        }
    }

    /**
     * Display missing items
     */
    private function displayMissingItems(array $missingIds, Collection $dbItems, string $message, string $idField, callable $formatter): void
    {
        if (count($missingIds) > 0) {
            $this->warn("\n⚠️ ".count($missingIds).' '.$message.':');
            foreach ($missingIds as $id) {
                $item = $dbItems->firstWhere($idField, $id);
                $this->line($formatter($item));
            }
        }
    }

    /**
     * Display missing Google Play items
     */
    private function displayMissingGoogleItems(array $missingIds, array $googleItems, string $message, string $idField, callable $formatter): void
    {
        if (count($missingIds) > 0) {
            $this->warn("\n⚠️ ".count($missingIds).' '.$message.':');
            foreach ($missingIds as $id) {
                $item = collect($googleItems)->firstWhere($idField, $id);

                // Skip if item is not an array
                if (! is_array($item)) {
                    $this->line("  • Warning: Invalid item format for ID: {$id}");

                    continue;
                }

                $this->line($formatter($item));
            }
        }
    }

    /**
     * Display items missing a field
     */
    private function displayMissingFields(Collection $items, string $message, callable $formatter): void
    {
        if ($items->count() > 0) {
            $this->warn("\n⚠️ ".$items->count().' '.$message.':');
            foreach ($items as $item) {
                $this->line($formatter($item));
            }
        }
    }

    /**
     * Display duplicate values from a model
     */
    private function displayDuplicates(string $label, callable $formatter): void
    {
        $field = 'product_id'; // Find duplicates using the model
        $duplicates = GooglePlayPlan::selectRaw('product_id, COUNT(*) as count')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            $this->error("\n❌ ".$duplicates->count()." duplicate {$label}s in database:");
            foreach ($duplicates as $duplicate) {
                $items = GooglePlayPlan::class::where('product_id', $duplicate->{$field})->get();
                $formatter($duplicate, $items);
            }
        }
    }
}
