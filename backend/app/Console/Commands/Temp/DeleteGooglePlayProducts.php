<?php

namespace App\Console\Commands\Temp;

use Exception;
use Illuminate\Console\Command;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use App\Syllaby\Subscriptions\Services\GooglePlayProductService;
use App\Syllaby\Subscriptions\Services\GooglePlaySubscriptionService;

class DeleteGooglePlayProducts extends Command
{
    protected $signature = 'google-play:delete
        {--products : Delete only products}
        {--subscriptions : Delete only subscriptions}
        {--deactivate : Deactivate subscriptions instead of deletion (preferred method)}
        {--force-deactivate-all : Force deactivation of all base plans (for published subscriptions)}
        {--skip-status-check : Skip running status check before deletion}
        {--dry-run : Show what would be deleted, but don\'t delete}
        {--force : Actually perform deletion (required unless dry-run)}';

    protected $description = 'Delete Google Play products and subscriptions (DANGEROUS)';

    /**
     * The subscription service instance.
     */
    protected GooglePlaySubscriptionService $subscriptionService;

    /**
     * The product service instance.
     */
    protected GooglePlayProductService $productService;

    /**
     * Execute the console command.
     */
    public function handle(
        GooglePlayProductService $productService,
        GooglePlaySubscriptionService $subscriptionService
    ) {
        $this->warn('DANGER: This will delete/deactivate products/subscriptions from Google Play!');
        $this->productService = $productService;
        $this->subscriptionService = $subscriptionService;

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $doProducts = $this->option('products');
        $doSubs = $this->option('subscriptions');
        $deactivate = $this->option('deactivate');
        $forceDeactivateAll = $this->option('force-deactivate-all');
        $skipStatusCheck = $this->option('skip-status-check');
        $checkAll = ! $doProducts && ! $doSubs;

        if (! $dryRun && ! $force) {
            $this->error('Use --force to actually delete. Use --dry-run to preview.');

            return 1;
        }

        if ($forceDeactivateAll) {
            $this->info('Will force-deactivate all subscription base plans');
        } elseif ($deactivate) {
            $this->info('Using DEACTIVATE mode for subscriptions (preferred according to Google Play API)');
        } else {
            $this->warn('Using DELETE mode which only works for DRAFT subscriptions. Consider using --deactivate instead.');
            $this->info('Note: Published subscriptions CANNOT be deleted, only deactivated, per Google Play API restrictions.');
        }

        try {
            $results = [
                'products' => ['deleted' => 0, 'failed' => 0],
                'subscriptions' => ['deleted' => 0, 'deactivated' => 0, 'failed' => 0],
            ];
            $errors = [];

            $this->logOperationStart($dryRun, $force, $deactivate, $doProducts, $doSubs, $checkAll, $forceDeactivateAll);

            // Run status check first (unless skipped)
            if (! $skipStatusCheck) {
                $this->info('Running status check before deletion...');
                Artisan::call('google-play:status');
                $this->line(Artisan::output());
            } else {
                $this->info('Status check skipped.');
            }

            // Products
            if ($checkAll || $doProducts) {
                $this->processProducts($dryRun, $results, $errors);
            }

            // Subscriptions
            if ($checkAll || $doSubs) {
                $this->processSubscriptions($dryRun, $deactivate, $forceDeactivateAll, $results, $errors);
            }

            Log::info('Google Play deletion/deactivation process completed', [
                'results' => $results,
                'error_count' => count($errors),
            ]);

            $this->displaySummary($results, $errors);

            return 0;
        } catch (Exception $e) {
            Log::error('Failed in Google Play deletion', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Log operation start
     */
    private function logOperationStart(
        bool $dryRun,
        bool $force,
        bool $deactivate,
        bool $doProducts,
        bool $doSubs,
        bool $checkAll,
        bool $forceDeactivateAll
    ): void {
        Log::info('Starting Google Play deletion/deactivation process', [
            'package_name' => config('google-play.package_name'),
            'dry_run' => $dryRun,
            'force' => $force,
            'deactivate' => $deactivate,
            'products' => $doProducts,
            'subscriptions' => $doSubs,
            'check_all' => $checkAll,
            'force_deactivate_all' => $forceDeactivateAll,
        ]);
    }

    /**
     * Process one-time products
     */
    private function processProducts(
        bool $dryRun,
        array &$results,
        array &$errors
    ): void {
        $this->info('Processing one-time products:');
        $products = Plan::whereIn('type', ['one_time', 'product'])
            ->with('googlePlayPlan')
            ->whereHas('googlePlayPlan')
            ->get();

        if ($products->isEmpty()) {
            $this->line('No one-time products found to delete.');
            Log::info('No one-time products found to delete');

            return;
        }

        Log::info('Starting product deletion process', [
            'product_count' => $products->count(),
            'dry_run' => $dryRun,
        ]);

        foreach ($products as $plan) {
            if (! $plan->googlePlayPlan) {
                continue;
            }

            $sku = $plan->googlePlayPlan->product_id;

            Log::info('Processing product', [
                'product_id' => $plan->id,
                'name' => $plan->name,
                'sku' => $sku,
                'dry_run' => $dryRun,
            ]);

            if ($dryRun) {
                $this->line("[DRY] Would delete product: {$sku} ({$plan->name})");

                continue;
            }

            try {
                $result = $this->productService->deleteProduct($sku);

                if ($result['success'] || $result['status'] === 404) {
                    $this->info("Deleted: {$sku} ({$plan->name})");
                    $results['products']['deleted']++;

                    // Delete the GooglePlayPlan record
                    $plan->googlePlayPlan->delete();
                } else {
                    $this->handleDeletionError(
                        $results,
                        $errors,
                        'products',
                        "Failed to delete {$sku}: {$result['status']} {$result['error']}",
                        $plan->id,
                        $sku,
                        $result
                    );
                }
            } catch (Exception $e) {
                $this->handleDeletionError(
                    $results,
                    $errors,
                    'products',
                    "Failed to delete {$sku}: {$e->getMessage()}",
                    $plan->id,
                    $sku,
                    null,
                    $e
                );
            }
        }

        Log::info('Completed product deletion process', [
            'deleted' => $results['products']['deleted'],
            'failed' => $results['products']['failed'],
        ]);
    }

    /**
     * Process subscriptions
     */
    private function processSubscriptions(
        bool $dryRun,
        bool $deactivate,
        bool $forceDeactivateAll,
        array &$results,
        array &$errors
    ): void {
        $this->info('Processing subscriptions:');

        $subs = Plan::whereIn('type', ['month', 'year'])
            ->with('googlePlayPlan')
            ->whereHas('googlePlayPlan')
            ->get();

        if ($subs->isEmpty()) {
            $this->line('No subscriptions found to process.');
            Log::info('No subscriptions found to process');

            return;
        }

        Log::info('Starting subscription processing', [
            'subscription_count' => $subs->count(),
            'dry_run' => $dryRun,
            'deactivate_mode' => $deactivate,
            'force_deactivate_all' => $forceDeactivateAll,
        ]);

        // First pass: deactivate all base plans if requested
        if ($forceDeactivateAll && ! $dryRun) {
            $this->info('Deactivating all base plans for subscriptions...');

            foreach ($subs as $plan) {
                if (! $plan->googlePlayPlan) {
                    continue;
                }

                $productId = $plan->googlePlayPlan->product_id;
                $basePlanId = $this->subscriptionService->generateBasePlanId($plan);

                try {
                    $result = $this->subscriptionService->deactivateSubscriptionBasePlan($productId, $basePlanId);

                    if ($result['success']) {
                        $this->info("Deactivated base plan for: {$productId} ({$plan->name})");
                        $results['subscriptions']['deactivated']++;

                        // Update Google Play status in database
                        $plan->googlePlayPlan->update([
                            'status' => 'inactive',
                        ]);
                    } else {
                        $this->handleDeletionError(
                            $results,
                            $errors,
                            'subscriptions',
                            "Failed to deactivate {$productId} base plan: {$result['status']} {$result['error']}",
                            $plan->id,
                            $productId,
                            $result
                        );
                    }
                } catch (Exception $e) {
                    $this->warn("Error deactivating base plan for {$productId}: {$e->getMessage()}");
                }
            }

            $this->info('Base plan deactivation completed.');

            return; // Exit after deactivation since we can't actually delete published subscriptions
        }

        // Regular processing: try to delete draft subscriptions or deactivate
        foreach ($subs as $plan) {
            if (! $plan->googlePlayPlan) {
                continue;
            }

            $productId = $plan->googlePlayPlan->product_id;

            Log::info('Processing subscription', [
                'plan_id' => $plan->id,
                'name' => $plan->name,
                'type' => $plan->type,
                'sku' => $productId,
                'dry_run' => $dryRun,
            ]);

            if ($dryRun) {
                if ($deactivate) {
                    $this->line("[DRY] Would deactivate subscription: {$productId} ({$plan->name})");
                } else {
                    $this->line("[DRY] Would attempt to delete subscription: {$productId} ({$plan->name})");
                }

                continue;
            }

            try {
                // Get the subscription details first to check status
                $subDetails = null;
                $canDelete = false; // Default to false - most subscriptions can't be deleted

                try {
                    $subDetails = $this->subscriptionService->getSubscription($productId);

                    // Check if we can delete - only DRAFT state subscriptions can be deleted
                    $canDelete = $subDetails && isset($subDetails['state']) && $subDetails['state'] === 'DRAFT';

                    Log::info('Retrieved subscription details', [
                        'sku' => $productId,
                        'state' => $subDetails['state'] ?? 'unknown',
                        'can_delete' => $canDelete,
                    ]);
                } catch (Exception $e) {
                    Log::warning("Could not retrieve subscription details: {$e->getMessage()}", [
                        'sku' => $productId,
                    ]);
                    // If we can't get details, default to deactivation to be safe
                    $canDelete = false;
                }

                if ($deactivate || ! $canDelete) {
                    // Deactivate approach
                    $basePlanId = $this->subscriptionService->generateBasePlanId($plan);
                    $result = $this->subscriptionService->deactivateSubscriptionBasePlan($productId, $basePlanId);

                    if ($result['success']) {
                        $this->info("Deactivated: {$productId} ({$plan->name})");
                        $results['subscriptions']['deactivated']++;

                        // Update Google Play status in database
                        $plan->googlePlayPlan->update([
                            'status' => 'inactive',
                        ]);
                    } else {
                        $this->handleDeletionError(
                            $results,
                            $errors,
                            'subscriptions',
                            "Failed to deactivate {$productId}: {$result['status']} {$result['error']}",
                            $plan->id,
                            $productId,
                            $result
                        );
                    }
                } else {
                    // Delete approach (only works for DRAFT subscriptions)
                    $result = $this->subscriptionService->deleteSubscription($productId);

                    if ($result['success'] || $result['status'] === 404) {
                        $this->info("Deleted: {$productId} ({$plan->name})");
                        $results['subscriptions']['deleted']++;

                        // Delete the GooglePlayPlan record
                        $plan->googlePlayPlan->delete();
                    } else {
                        // If deletion fails due to state, try deactivating instead
                        if (strpos($result['error'] ?? '', 'Cannot delete a subscription') !== false) {
                            $this->warn("Cannot delete {$productId}, trying deactivation instead...");
                            $basePlanId = $this->subscriptionService->generateBasePlanId($plan);

                            $fallbackResult = $this->subscriptionService->deactivateSubscriptionBasePlan($productId, $basePlanId);

                            if ($fallbackResult['success']) {
                                $this->info("Deactivated: {$productId} ({$plan->name})");
                                $results['subscriptions']['deactivated']++;

                                // Update Google Play status in database
                                $plan->googlePlayPlan->update([
                                    'status' => 'inactive',
                                ]);
                            } else {
                                $this->handleDeletionError(
                                    $results,
                                    $errors,
                                    'subscriptions',
                                    "Failed to deactivate {$productId}: {$fallbackResult['status']} {$fallbackResult['error']}",
                                    $plan->id,
                                    $productId,
                                    $fallbackResult
                                );
                            }
                        } else {
                            $this->handleDeletionError(
                                $results,
                                $errors,
                                'subscriptions',
                                "Failed to delete {$productId}: {$result['status']} {$result['error']}",
                                $plan->id,
                                $productId,
                                $result
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                $this->handleDeletionError(
                    $results,
                    $errors,
                    'subscriptions',
                    "Failed to process {$productId}: {$e->getMessage()}",
                    $plan->id,
                    $productId,
                    null,
                    $e
                );
            }
        }

        Log::info('Completed subscription processing', [
            'deleted' => $results['subscriptions']['deleted'],
            'deactivated' => $results['subscriptions']['deactivated'],
            'failed' => $results['subscriptions']['failed'],
        ]);
    }

    /**
     * Handle error during deletion process
     */
    private function handleDeletionError(
        array &$results,
        array &$errors,
        string $type,
        string $message,
        int $itemId,
        string $itemIdentifier,
        ?array $result = null
    ): void {
        $this->error($message);
        $errors[] = $message;
        $results[$type]['failed']++;

        $logData = [
            'item_id' => $itemId,
            'item_identifier' => $itemIdentifier,
            'error_message' => $message,
        ];

        if ($result) {
            $logData['status_code'] = $result['status'] ?? 'unknown';
            $logData['response'] = $result['error'] ?? '';
        }

        Log::error("Failed to process {$type} item", $logData);
    }

    /**
     * Display operation summary
     */
    private function displaySummary(array $results, array $errors): void
    {
        $this->info('Summary:');
        $this->info("Products: {$results['products']['deleted']} deleted, {$results['products']['failed']} failed");
        $this->info("Subscriptions: {$results['subscriptions']['deleted']} deleted, {$results['subscriptions']['deactivated']} deactivated, {$results['subscriptions']['failed']} failed");

        Log::info('Operation summary', [
            'products_deleted' => $results['products']['deleted'],
            'products_failed' => $results['products']['failed'],
            'subscriptions_deleted' => $results['subscriptions']['deleted'],
            'subscriptions_deactivated' => $results['subscriptions']['deactivated'],
            'subscriptions_failed' => $results['subscriptions']['failed'],
            'total_errors' => count($errors),
        ]);

        if (count($errors)) {
            $this->warn(count($errors).' errors occurred:');
            foreach ($errors as $index => $error) {
                $this->line("  - {$error}");
                Log::error('Operation error #'.($index + 1), [
                    'error_message' => $error,
                ]);
            }
        }

        $this->info('Done.');
    }
}
