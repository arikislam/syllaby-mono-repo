<?php

namespace App\Syllaby\Subscriptions\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use App\Syllaby\Subscriptions\Contracts\GooglePlayApiClientInterface;

class FetchGooglePlayOffersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-play:fetch-offers 
                            {--plan-id= : Fetch offers for a specific Google Play plan ID}
                            {--product-id= : Fetch offers for a specific product ID}
                            {--force : Force update even if offers already exist}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Google Play offers for subscription plans and save them to the database';

    /**
     * Google Play API client
     */
    private GooglePlayApiClientInterface $apiClient;

    /**
     * Create a new command instance.
     */
    public function __construct(GooglePlayApiClientInterface $apiClient)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Google Play offers fetch...');

        if ($this->option('dry-run')) {
            $this->warn('Running in DRY-RUN mode - no changes will be saved');
        }

        try {
            $planId = $this->option('plan-id');
            $productId = $this->option('product-id');
            $force = $this->option('force');

            if ($planId) {
                return $this->fetchOffersForPlan((int) $planId, $force);
            } elseif ($productId) {
                return $this->fetchOffersForProduct($productId, $force);
            } else {
                return $this->fetchOffersForAllPlans($force);
            }
        } catch (Exception $e) {
            $this->error('Failed to fetch Google Play offers: '.$e->getMessage());
            Log::error('Google Play offers fetch failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Fetch offers for a specific plan ID.
     */
    private function fetchOffersForPlan(int $planId, bool $force): int
    {
        $googlePlayPlan = GooglePlayPlan::find($planId);

        if (! $googlePlayPlan) {
            $this->error("Google Play plan with ID {$planId} not found");

            return 1;
        }

        if (! $googlePlayPlan->isSubscription()) {
            $this->warn("Plan {$planId} is not a subscription - offers are only available for subscriptions");

            return 0;
        }

        $this->info("Fetching offers for plan {$planId} (Product: {$googlePlayPlan->product_id}, Base Plan: {$googlePlayPlan->base_plan_id})...");

        Log::info('Plan details for offers fetch', [
            'plan_id' => $planId,
            'product_id' => $googlePlayPlan->product_id,
            'base_plan_id' => $googlePlayPlan->base_plan_id,
            'product_type' => $googlePlayPlan->product_type,
            'status' => $googlePlayPlan->status,
        ]);

        $result = $this->fetchAndSaveOffers($googlePlayPlan, $force);

        if ($result['success']) {
            if ($result['offers_count'] > 0) {
                $this->info("Successfully fetched {$result['offers_count']} offers for plan {$planId}");
            } else {
                $this->warn("No offers found for plan {$planId}. This is normal if no offers are configured in Google Play Console.");
                $this->line("To add offers: Google Play Console → App → Subscriptions → {$googlePlayPlan->product_id} → Offers");
            }
        } else {
            $this->error("Failed to fetch offers for plan {$planId}: ".$result['error']);

            return 1;
        }

        return 0;
    }

    /**
     * Fetch offers for a specific product ID.
     */
    private function fetchOffersForProduct(string $productId, bool $force): int
    {
        $googlePlayPlan = GooglePlayPlan::where('product_id', $productId)->first();

        if (! $googlePlayPlan) {
            $this->error("Google Play plan with product ID {$productId} not found");

            return 1;
        }

        if (! $googlePlayPlan->isSubscription()) {
            $this->warn("Product {$productId} is not a subscription - offers are only available for subscriptions");

            return 0;
        }

        $this->info("Fetching offers for product {$productId}...");

        $result = $this->fetchAndSaveOffers($googlePlayPlan, $force);

        if ($result['success']) {
            if ($result['offers_count'] > 0) {
                $this->info("Successfully fetched {$result['offers_count']} offers for product {$productId}");
            } else {
                $this->warn("No offers found for product {$productId}. This is normal if no offers are configured in Google Play Console.");
                $this->line("To add offers: Google Play Console → App → Subscriptions → {$productId} → Offers");
            }
        } else {
            $this->error("Failed to fetch offers for product {$productId}: ".$result['error']);

            return 1;
        }

        return 0;
    }

    /**
     * Fetch offers for all subscription plans.
     */
    private function fetchOffersForAllPlans(bool $force): int
    {
        $query = GooglePlayPlan::subscriptions();

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('offers')
                    ->orWhereJsonLength('offers', 0);
            });
        }

        $subscriptionPlans = $query->get();

        if ($subscriptionPlans->isEmpty()) {
            $this->info('No subscription plans found that need offers fetching');

            return 0;
        }

        $this->info("Found {$subscriptionPlans->count()} subscription plans to fetch offers for");

        $totalProcessed = 0;
        $totalSuccessful = 0;
        $totalFailed = 0;

        $progressBar = $this->output->createProgressBar($subscriptionPlans->count());
        $progressBar->start();

        foreach ($subscriptionPlans as $plan) {
            $totalProcessed++;

            $result = $this->fetchAndSaveOffers($plan, $force);

            if ($result['success']) {
                $totalSuccessful++;
                $this->line(" ✓ Plan {$plan->id} ({$plan->product_id}): {$result['offers_count']} offers");
            } else {
                $totalFailed++;
                $this->line(" ✗ Plan {$plan->id} ({$plan->product_id}): {$result['error']}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Offers fetch completed:');
        $this->info("- Total processed: {$totalProcessed}");
        $this->info("- Successful: {$totalSuccessful}");
        $this->info("- Failed: {$totalFailed}");

        if ($totalSuccessful > 0 && $totalFailed === 0) {
            $this->newLine();
            $this->line('📝 <comment>Note:</comment> If most plans show 0 offers, this is normal.');
            $this->line('   Offers must be created in Google Play Console first:');
            $this->line('   <info>Google Play Console → Your App → Subscriptions → [Product] → Offers</info>');
        }

        return $totalFailed > 0 ? 1 : 0;
    }

    /**
     * Fetch and save offers for a Google Play plan.
     */
    private function fetchAndSaveOffers(GooglePlayPlan $googlePlayPlan, bool $force): array
    {
        Log::info('Starting fetch and save offers process', [
            'plan_id' => $googlePlayPlan->id,
            'product_id' => $googlePlayPlan->product_id,
            'base_plan_id' => $googlePlayPlan->base_plan_id,
            'force' => $force,
            'existing_offers_count' => ! empty($googlePlayPlan->offers) ? count($googlePlayPlan->offers) : 0,
        ]);

        // Validate required fields for offers endpoint
        if (empty($googlePlayPlan->base_plan_id)) {
            Log::error('Cannot fetch offers - base_plan_id is missing', [
                'plan_id' => $googlePlayPlan->id,
                'product_id' => $googlePlayPlan->product_id,
                'base_plan_id' => $googlePlayPlan->base_plan_id,
            ]);

            return [
                'success' => false,
                'error' => 'Base plan ID is required to fetch offers',
                'offers_count' => 0,
            ];
        }

        try {
            // Check if offers already exist and force is not enabled
            if (! $force && ! empty($googlePlayPlan->offers)) {
                Log::info('Skipping offers fetch - offers already exist', [
                    'plan_id' => $googlePlayPlan->id,
                    'product_id' => $googlePlayPlan->product_id,
                    'existing_offers_count' => count($googlePlayPlan->offers),
                ]);

                return [
                    'success' => false,
                    'error' => 'Offers already exist (use --force to update)',
                    'offers_count' => count($googlePlayPlan->offers),
                ];
            }

            Log::info('Proceeding to fetch offers from API', [
                'plan_id' => $googlePlayPlan->id,
                'product_id' => $googlePlayPlan->product_id,
                'base_plan_id' => $googlePlayPlan->base_plan_id,
            ]);

            // Fetch offers from Google Play API
            $offers = $this->fetchOffersFromApi($googlePlayPlan->product_id, $googlePlayPlan->base_plan_id);

            Log::info('Successfully fetched offers from API', [
                'plan_id' => $googlePlayPlan->id,
                'product_id' => $googlePlayPlan->product_id,
                'base_plan_id' => $googlePlayPlan->base_plan_id,
                'offers_count' => count($offers),
                'offers_data' => $offers,
            ]);

            if ($this->option('dry-run')) {
                Log::info('DRY-RUN: Would save offers to database', [
                    'plan_id' => $googlePlayPlan->id,
                    'product_id' => $googlePlayPlan->product_id,
                    'offers_count' => count($offers),
                ]);

                return [
                    'success' => true,
                    'offers_count' => count($offers),
                    'message' => 'DRY-RUN: Would save '.count($offers).' offers',
                ];
            }

            Log::info('Saving offers to database', [
                'plan_id' => $googlePlayPlan->id,
                'product_id' => $googlePlayPlan->product_id,
                'offers_count' => count($offers),
            ]);

            // Save offers to the database
            $googlePlayPlan->update([
                'offers' => $offers,
            ]);

            Log::info('Google Play offers fetched and saved successfully', [
                'plan_id' => $googlePlayPlan->id,
                'product_id' => $googlePlayPlan->product_id,
                'base_plan_id' => $googlePlayPlan->base_plan_id,
                'offers_count' => count($offers),
                'saved_offers' => $offers,
            ]);

            return [
                'success' => true,
                'offers_count' => count($offers),
            ];
        } catch (Exception $e) {
            Log::error('Exception occurred while fetching offers for Google Play plan', [
                'plan_id' => $googlePlayPlan->id,
                'product_id' => $googlePlayPlan->product_id,
                'base_plan_id' => $googlePlayPlan->base_plan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'offers_count' => 0,
            ];
        }
    }

    /**
     * Fetch offers from Google Play API.
     */
    private function fetchOffersFromApi(string $productId, string $basePlanId): array
    {
        $endpoint = "/subscriptions/{$productId}/basePlans/{$basePlanId}/offers";

        Log::info('Fetching offers from Google Play API', [
            'endpoint' => $endpoint,
            'product_id' => $productId,
            'base_plan_id' => $basePlanId,
        ]);

        $result = $this->apiClient->makeRequest('get', $endpoint, [], [
            'context' => 'fetch_offers',
            'product_id' => $productId,
            'base_plan_id' => $basePlanId,
        ]);

        // Log the full API response for debugging
        Log::info('Google Play API response for offers', [
            'product_id' => $productId,
            'base_plan_id' => $basePlanId,
            'success' => $result['success'],
            'status' => $result['status'] ?? null,
            'response_data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ]);

        if (! $result['success']) {
            Log::error('Google Play offers API request failed', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
                'status' => $result['status'] ?? null,
                'error' => $result['error'] ?? 'Unknown error',
                'full_response' => $result,
            ]);

            throw new Exception('API request failed: '.($result['error'] ?? 'Unknown error'));
        }

        $offersData = $result['data']['subscriptionOffers'] ?? [];

        Log::info('Processing offers data', [
            'product_id' => $productId,
            'base_plan_id' => $basePlanId,
            'offers_count' => count($offersData),
            'raw_offers_data' => $offersData,
        ]);

        // Process and clean up offers data
        $processedOffers = [];

        foreach ($offersData as $index => $offer) {
            Log::debug('Processing individual offer', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
                'offer_index' => $index,
                'offer_data' => $offer,
            ]);

            $processedOffer = [
                'offer_id' => $offer['offerId'] ?? null,
                'state' => $offer['state'] ?? null,
                'offer_tags' => $offer['offerTags'] ?? [],
                'phases' => $this->parseOfferPhases($offer['phases'] ?? []),
                'targeting' => $this->processTargeting($offer['targeting'] ?? []),
            ];

            Log::debug('Processed offer result', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
                'offer_index' => $index,
                'processed_offer' => $processedOffer,
            ]);

            $processedOffers[] = $processedOffer;
        }

        Log::info('Completed processing offers', [
            'product_id' => $productId,
            'base_plan_id' => $basePlanId,
            'processed_offers_count' => count($processedOffers),
            'final_processed_offers' => $processedOffers,
        ]);

        return $processedOffers;
    }

    /**
     * Parse offer phases with enhanced trial detection.
     */
    private function parseOfferPhases(array $phases): array
    {
        return collect($phases)->map(function ($phase, $index) {
            $parsedPhase = [
                'phase_index' => $index,
                'duration' => $phase['duration'] ?? null,
                'recurrence_count' => $phase['recurrenceCount'] ?? null,
                'regional_configs' => [],
                'is_trial' => false,
            ];

            // Check if this phase is a trial based on pricing
            $isTrial = false;

            // Parse regional configs
            if (isset($phase['regionalConfigs'])) {
                foreach ($phase['regionalConfigs'] as $region => $config) {
                    $regionConfig = [
                        'region_code' => $region,
                    ];

                    // Check for free pricing (trial indicator)
                    if (isset($config['free'])) {
                        $regionConfig['free'] = true;
                        $isTrial = true;
                    } elseif (isset($config['absolutePrice'])) {
                        $price = $config['absolutePrice'];
                        $regionConfig['price_units'] = $price['units'] ?? '0';
                        $regionConfig['price_nanos'] = $price['nanos'] ?? 0;
                        $regionConfig['currency_code'] = $price['currencyCode'] ?? null;

                        // If price is 0, it's a trial
                        if ((int) $regionConfig['price_units'] === 0 && (int) $regionConfig['price_nanos'] === 0) {
                            $isTrial = true;
                        }
                    } elseif (isset($config['relativePrice'])) {
                        $regionConfig['relative_discount'] = $config['relativePrice']['relativeDiscount'] ?? null;
                    }

                    $parsedPhase['regional_configs'][] = $regionConfig;
                }
            }

            // Parse other regions config
            if (isset($phase['otherRegionsConfig'])) {
                $otherConfig = [
                    'region_code' => 'OTHER',
                ];

                if (isset($phase['otherRegionsConfig']['free'])) {
                    $otherConfig['free'] = true;
                    $isTrial = true;
                } elseif (isset($phase['otherRegionsConfig']['absolutePrice'])) {
                    $price = $phase['otherRegionsConfig']['absolutePrice'];
                    $otherConfig['price_units'] = $price['units'] ?? '0';
                    $otherConfig['price_nanos'] = $price['nanos'] ?? 0;
                    $otherConfig['currency_code'] = $price['currencyCode'] ?? null;

                    // If price is 0, it's a trial
                    if ((int) $otherConfig['price_units'] === 0 && (int) $otherConfig['price_nanos'] === 0) {
                        $isTrial = true;
                    }
                } elseif (isset($phase['otherRegionsConfig']['relativePrice'])) {
                    $otherConfig['relative_discount'] = $phase['otherRegionsConfig']['relativePrice']['relativeDiscount'] ?? null;
                }

                $parsedPhase['regional_configs'][] = $otherConfig;
            }

            $parsedPhase['is_trial'] = $isTrial;

            return $parsedPhase;
        })->toArray();
    }

    /**
     * Process targeting information to only include essential data.
     */
    private function processTargeting(array $targeting): ?array
    {
        if (empty($targeting)) {
            return null;
        }

        // Only store acquisition rule scope if it's specific, since most are "anySubscriptionInApp"
        $acquisitionRule = $targeting['acquisitionRule'] ?? [];
        $scope = $acquisitionRule['scope'] ?? [];

        // If it's the default "anySubscriptionInApp", don't store it
        if (isset($scope['anySubscriptionInApp']) && empty($scope['anySubscriptionInApp'])) {
            return null;
        }

        // Only store non-standard targeting rules
        return [
            'acquisition_rule' => $acquisitionRule,
        ];
    }
} 