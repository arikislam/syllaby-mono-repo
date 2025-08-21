<?php

namespace App\Syllaby\Subscriptions\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\Services\GooglePlayPlanService;

class SyncGooglePlayCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sync:google-play
                            {--plan= : Specific plan ID to sync}
                            {--force : Force sync even if not marked for sync}
                            {--fake : Don\'t make real API calls, just update local DB}
                            {--only-subscriptions : Push only subscription products}
                            {--only-products : Push only one-time products}';

    /**
     * The console command description.
     */
    protected $description = 'Sync plans with Google Play';

    /**
     * Google Play service
     */
    protected GooglePlayPlanService $googlePlayService;

    /**
     * Create a new command instance.
     */
    public function __construct(GooglePlayPlanService $googlePlayService)
    {
        parent::__construct();

        $this->googlePlayService = $googlePlayService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Google Play synchronization...');

        if ($this->option('fake')) {
            $this->info('Running in FAKE mode - no actual API calls will be made');
        }

        try {
            if ($planId = $this->option('plan')) {
                return $this->syncSpecificPlan((int) $planId);
            }

            return $this->syncAllItems();

        } catch (Exception $e) {
            $this->error("Google Play sync failed: {$e->getMessage()}");

            return 1;
        }
    }

    /**
     * Sync a specific plan
     */
    private function syncSpecificPlan(int $planId): int
    {
        $plan = Plan::find($planId);
        if (! $plan) {
            $this->error("Plan with ID {$planId} not found");

            return 1;
        }

        $this->info("Syncing plan {$plan->id} ({$plan->name})...");

        $result = $this->googlePlayService->syncPlan(
            $plan,
            $this->option('force'),
            false,
            $this->option('fake')
        );

        if ($result['success']) {
            $this->info('Plan synced successfully');
        } else {
            $this->warn("Plan sync skipped: {$result['message']}");
        }

        return 0;
    }

    /**
     * Sync all eligible plans
     */
    private function syncAllItems(): int
    {
        $isFake = $this->option('fake');
        $isForce = $this->option('force');
        $onlySubscriptions = $this->option('only-subscriptions');
        $onlyProducts = $this->option('only-products');

        if ($onlyProducts) {
            $this->info('Syncing one-time products...');
            $results = $this->googlePlayService->syncOnlyProducts($isFake);
            $this->displayResults('One-time Products', $results);
        } else {
            $this->info('Syncing plans...');
            $results = $this->googlePlayService->syncAllPlans(false, false, $isFake, true, $onlySubscriptions, $isForce);
            $this->displayResults('Plans', $results);
        }

        $this->info('Google Play synchronization completed successfully');

        return 0;
    }

    /**
     * Display sync results
     */
    private function displayResults(string $type, array $results): void
    {
        $this->info("{$type} sync summary:");
        $this->info("- Total: {$results['total']}");
        $this->info("- Processed: {$results['processed']}");
        $this->info("- Successful: {$results['successful']}");
        $this->info("- Failed: {$results['failed']}");

        if ($results['failed'] > 0 && ! empty($results['errors'])) {
            $this->warn('Failed syncs (no GooglePlayPlan entries created):');
            foreach ($results['errors'] as $error) {
                $this->error("  - Plan {$error['plan_id']} ({$error['plan_name']}): {$error['error']}");
            }

            $this->newLine();
            $this->line('<comment>Note:</comment> GooglePlayPlan entries are only created after successful Google Play API calls.');
            $this->line('Failed syncs do not leave orphaned database records.');
        }
    }
}
