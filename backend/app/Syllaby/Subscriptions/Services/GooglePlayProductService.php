<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;
use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use App\Syllaby\Subscriptions\GooglePlayPurchase;
use App\Syllaby\Subscriptions\Contracts\GooglePlayProductServiceInterface;

/**
 * Google Play Product Service for managing one-time products
 */
class GooglePlayProductService extends GooglePlayBaseService implements GooglePlayProductServiceInterface
{
    /**
     * Verify a product purchase token with Google Play API.
     *
     * @param  string  $purchaseToken  The purchase token to verify
     * @param  string  $productId  The product SKU
     * @return array{verified: bool, data: array|null, error: string|null}
     */
    public function verifyPurchaseToken(string $purchaseToken, string $productId): array
    {
        try {
            $endpoint = "/purchases/products/{$productId}/tokens/{$purchaseToken}";
            $result = $this->makeApiRequest('get', $endpoint, [], [
                'purchase_token' => $purchaseToken,
                'product_id' => $productId,
                'type' => 'product',
            ]);

            if ($result['success'] && isset($result['data'])) {
                $this->logDebug('Product purchase token verified successfully', [
                    'purchase_token' => $purchaseToken,
                    'product_id' => $productId,
                ]);

                return [
                    'verified' => true,
                    'data' => $result['data'],
                    'error' => null,
                ];
            }

            return [
                'verified' => false,
                'data' => null,
                'error' => $result['error'] ?? 'Verification failed',
            ];
        } catch (Exception $e) {
            $this->logError('Exception verifying product purchase token', [
                'purchase_token' => $purchaseToken,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'verified' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Map Google Play purchase status based on API data.
     *
     * @param  array  $apiData  The API response data
     * @return string The mapped status
     */
    public function mapPurchaseStatus(array $apiData): string
    {
        // Check refund
        if (isset($apiData['refundedQuantity']) && $apiData['refundedQuantity'] > 0) {
            return GooglePlayPurchase::STATUS_REFUNDED;
        }

        // Check consumption
        if (isset($apiData['consumptionState']) && $apiData['consumptionState'] === 1) {
            return GooglePlayPurchase::STATUS_CONSUMED;
        }

        // Check acknowledgement
        if (isset($apiData['acknowledgementState']) && $apiData['acknowledgementState'] === 1) {
            return GooglePlayPurchase::STATUS_ACKNOWLEDGED;
        }

        // Map purchase state
        $purchaseState = $apiData['purchaseState'] ?? GooglePlayPurchase::PURCHASE_STATE_PURCHASED;

        return match ($purchaseState) {
            GooglePlayPurchase::PURCHASE_STATE_PENDING => GooglePlayPurchase::STATUS_PENDING,
            GooglePlayPurchase::PURCHASE_STATE_CANCELED => GooglePlayPurchase::STATUS_REFUNDED, // Canceled maps to refunded
            default => GooglePlayPurchase::STATUS_PURCHASED,
        };
    }

    public function sync(mixed $plan, bool $force = false, bool $isTest = false, bool $isFake = false): array
    {
        if (! $plan instanceof Plan) {
            return ['success' => false, 'error' => 'Invalid plan type'];
        }

        return $this->pushProduct($plan, $isTest, $isFake);
    }

    public function syncAll(bool $isTest = false, bool $isFake = false): array
    {
        $plans = Plan::whereIn('type', ['one_time', 'product'])
            ->with('googlePlayPlan')
            ->needsGooglePlaySync()
            ->get();

        return $this->processPlansBatch($plans, $isTest, $isFake);
    }

    public function generateProductSku(Plan $plan): string
    {
        return $this->generateUniqueId($plan->type, $plan->name);
    }

    public function fetchAllProducts(): array
    {
        return $this->fetchFromEndpoint('/inappproducts', 'fetch_all_products', 'inappproduct');
    }

    public function deleteProduct(string $sku): array
    {
        try {
            return $this->makeApiRequest('delete', "/inappproducts/{$sku}", [], [
                'context' => 'delete_product',
                'sku' => $sku,
            ]);
        } catch (Exception $e) {
            return $this->handleApiException($e, 'delete_product', ['sku' => $sku]);
        }
    }

    private function pushProduct(Plan $plan, bool $isTest = false, bool $isFake = false): array
    {
        $createdNewGooglePlayPlan = false;
        $googlePlayPlan = null;

        try {
            // Prepare Google Play plan data but don't create yet if it doesn't exist
            $googlePlayPlanData = $this->prepareGooglePlayPlanData($plan, $isTest);
            $googlePlayPlan = $this->getExistingGooglePlayPlan($plan);

            if (! $googlePlayPlan) {
                // Mark that we'll need to create a new plan
                $createdNewGooglePlayPlan = true;
            } else {
                // Update existing plan price if needed
                $computedPriceMicros = (int) ($plan->price * 10000);
                if ($googlePlayPlan->price_micros !== $computedPriceMicros) {
                    $googlePlayPlan->update(['price_micros' => $computedPriceMicros]);
                }
            }

            $this->validatePrice($googlePlayPlanData['price_micros'], $plan->id);

            if ($isFake) {
                // For fake mode, create the GooglePlayPlan if needed
                if (! $googlePlayPlan) {
                    $googlePlayPlan = GooglePlayPlan::create($googlePlayPlanData);
                }

                return $this->handleFakeMode($plan, $googlePlayPlan, $isTest);
            }

            // Try to sync with Google Play first
            $result = $this->createOrUpdateProductWithData($plan, $googlePlayPlanData, $googlePlayPlan, $isTest);

            if ($result['success']) {
                // Only create GooglePlayPlan after successful API call
                if ($createdNewGooglePlayPlan) {
                    $googlePlayPlan = GooglePlayPlan::create($googlePlayPlanData);
                }

                // Save the response data if we have a GooglePlayPlan
                if ($googlePlayPlan) {
                    $this->saveProductResponse($googlePlayPlan, $result['data'], $isTest);
                }

                return [
                    'success' => true,
                    'plan_id' => $plan->id,
                    'sku' => $googlePlayPlanData['product_id'],
                    'data' => $result['data'],
                ];
            } else {
                // API call failed - don't create GooglePlayPlan
                return [
                    'success' => false,
                    'plan_id' => $plan->id,
                    'sku' => $googlePlayPlanData['product_id'],
                    'error' => $result['error'] ?? 'Google Play API call failed',
                ];
            }
        } catch (Exception $e) {
            // If we created a GooglePlayPlan but API failed, clean it up
            if ($createdNewGooglePlayPlan && $googlePlayPlan && $googlePlayPlan->exists) {
                $googlePlayPlan->delete();
                $this->logInfo('Cleaned up GooglePlayPlan after API failure', [
                    'plan_id' => $plan->id,
                    'google_play_plan_id' => $googlePlayPlan->id,
                ]);
            }

            return $this->handleApiException($e, 'push_product', ['plan_id' => $plan->id]);
        }
    }

    private function getExistingGooglePlayPlan(Plan $plan): ?GooglePlayPlan
    {
        if (! $plan->relationLoaded('googlePlayPlan')) {
            $plan->load('googlePlayPlan');
        }

        return $plan->googlePlayPlan;
    }

    private function prepareGooglePlayPlanData(Plan $plan, bool $isTest): array
    {
        $computedPriceMicros = (int) ($plan->price * 10000);

        return [
            'product_id' => $this->generateProductSku($plan),
            'product_type' => 'inapp',
            'name' => $plan->name,
            'status' => $isTest ? 'inactive' : 'active',
            'plan_id' => $plan->id,
            'price_micros' => $computedPriceMicros,
            'currency_code' => strtoupper($plan->currency ?? 'USD'),
        ];
    }

    private function createOrUpdateProductWithData(Plan $plan, array $googlePlayPlanData, ?GooglePlayPlan $googlePlayPlan, bool $isTest): array
    {
        $productData = $this->prepareProductDataFromArray($plan, $googlePlayPlanData, $isTest);

        // Try to create first
        $result = $this->makeApiRequest('post', '/inappproducts', $productData, [
            'context' => 'create_product',
            'plan_id' => $plan->id,
        ]);

        // If creation fails with conflict, try update
        if (! $result['success'] && $result['status'] === 409) {
            $result = $this->makeApiRequest('put', "/inappproducts/{$googlePlayPlanData['product_id']}", $productData, [
                'context' => 'update_product',
                'plan_id' => $plan->id,
            ]);
        }

        return [
            'success' => $result['success'],
            'plan_id' => $plan->id,
            'sku' => $googlePlayPlanData['product_id'],
            'data' => $result['data'] ?? [],
            'error' => $result['error'] ?? null,
        ];
    }

    private function createOrUpdateProduct(Plan $plan, GooglePlayPlan $googlePlayPlan, bool $isTest): array
    {
        $productData = $this->prepareProductData($plan, $googlePlayPlan, $isTest);

        // Try to create first
        $result = $this->makeApiRequest('post', '/inappproducts', $productData, [
            'context' => 'create_product',
            'plan_id' => $plan->id,
        ]);

        // If creation fails with conflict, try update
        if (! $result['success'] && $result['status'] === 409) {
            $result = $this->makeApiRequest('put', "/inappproducts/{$googlePlayPlan->product_id}", $productData, [
                'context' => 'update_product',
                'plan_id' => $plan->id,
            ]);
        }

        if ($result['success']) {
            $this->saveProductResponse($googlePlayPlan, $result['data'], $isTest);
        }

        return [
            'success' => $result['success'],
            'plan_id' => $plan->id,
            'sku' => $googlePlayPlan->product_id,
            'data' => $result['data'] ?? [],
        ];
    }

    private function prepareProductDataFromArray(Plan $plan, array $googlePlayPlanData, bool $isTest): array
    {
        $prices = $this->prepareRegionalPricesUsingApi($googlePlayPlanData);

        return [
            'packageName' => $this->packageName,
            'sku' => $googlePlayPlanData['product_id'],
            'status' => $isTest ? 'inactive' : 'active',
            'purchaseType' => 'managedUser',
            'defaultLanguage' => 'en-US',
            'listings' => [
                'en-US' => [
                    'title' => $googlePlayPlanData['name'],
                    'description' => 'Digital product for Syllaby platform. '.$plan->name,
                ],
            ],
            'defaultPrice' => [
                'currency' => $googlePlayPlanData['currency_code'],
                'priceMicros' => (string) $googlePlayPlanData['price_micros'],
            ],
            'prices' => $prices,
        ];
    }

    private function prepareProductData(Plan $plan, GooglePlayPlan $googlePlayPlan, bool $isTest): array
    {
        $googlePlayPlanData = [
            'price_micros' => $googlePlayPlan->price_micros,
            'currency_code' => $googlePlayPlan->currency_code,
        ];
        $prices = $this->prepareRegionalPricesUsingApi($googlePlayPlanData);

        return [
            'packageName' => $this->packageName,
            'sku' => $googlePlayPlan->product_id,
            'status' => $isTest ? 'inactive' : 'active',
            'purchaseType' => 'managedUser',
            'defaultLanguage' => 'en-US',
            'listings' => [
                'en-US' => [
                    'title' => $googlePlayPlan->getGooglePlayTitle(),
                    'description' => $googlePlayPlan->getGooglePlayDescription() ?: 'Digital product for Syllaby platform. '.$plan->name,
                ],
            ],
            'defaultPrice' => [
                'currency' => $googlePlayPlan->currency_code,
                'priceMicros' => (string) $googlePlayPlan->price_micros,
            ],
            'prices' => $prices,
        ];
    }

    private function saveProductResponse(GooglePlayPlan $googlePlayPlan, array $responseData, bool $isTest): void
    {
        $googlePlayPlan->update([
            'status' => Arr::get($responseData, 'status', $isTest ? 'inactive' : 'active'),
            'metadata' => $responseData,
        ]);

        $googlePlayPlan->markAsSynced();
    }

    private function handleFakeMode(Plan $plan, GooglePlayPlan $googlePlayPlan, bool $isTest): array
    {
        $googlePlayPlan->update([
            'status' => $isTest ? 'inactive' : 'active',
        ]);

        $googlePlayPlan->markAsSynced();

        return [
            'success' => true,
            'status' => 'synced_locally',
            'plan_id' => $plan->id,
            'sku' => $googlePlayPlan->product_id,
        ];
    }

    private function processPlansBatch(mixed $plans, bool $isTest, bool $isFake): array
    {
        $results = [
            'total' => $plans->count(),
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($plans as $plan) {
            $results['processed']++;
            $response = $this->pushProduct($plan, $isTest, $isFake);

            if ($response['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'error' => $response['error'] ?? 'Unknown error',
                ];
            }
        }

        return $results;
    }

    /**
     * Prepare regional prices using Google Play's convertRegionPrices API.
     * This automatically calculates prices for all regions where the app is published.
     */
    private function prepareRegionalPricesUsingApi(array $googlePlayPlanData): array
    {
        $priceMicros = $googlePlayPlanData['price_micros'];
        $currencyCode = $googlePlayPlanData['currency_code'];

        // Convert micros to units and nanos for the API
        $units = floor($priceMicros / 1000000);
        $nanos = ($priceMicros % 1000000) * 1000;

        $requestData = [
            'price' => [
                'currencyCode' => $currencyCode,
                'units' => (string) $units,
                'nanos' => $nanos,
            ],
        ];

        try {
            $result = $this->makeApiRequest('post', '/pricing:convertRegionPrices', $requestData, [
                'context' => 'convert_region_prices',
            ]);

            if ($result['success'] && isset($result['data']['convertedRegionPrices'])) {
                // Based on testing, these regions are not billable for this app
                $knownNonBillableRegions = [
                    'AG', 'AL', 'AM', 'AO', 'AR', 'AW', 'AZ', 'BA', 'BF', 'BJ', 'BS', 'BW', 'BY', 'BZ',
                    'CD', 'CF', 'CG', 'CV', 'DJ', 'DM', 'DO', 'ER', 'FJ', 'GA', 'GD', 'GM', 'GN', 'GT',
                    'GW', 'HN', 'HT', 'IS', 'JM', 'KG', 'KM', 'KN', 'LA', 'LB', 'LC', 'LR', 'LY', 'MD',
                    'MK', 'ML', 'MT', 'MU', 'MV', 'MZ', 'NA', 'NE', 'NI', 'NP', 'PG', 'RW', 'SB', 'SC',
                    'SL', 'SN', 'SO', 'SR', 'TD', 'TG', 'TJ', 'TM', 'TN', 'TO', 'TT', 'UG', 'UY', 'UZ',
                    'VE', 'VU', 'WS', 'YE', 'ZM', 'ZW', 'SM', 'VA', 'VG', 'TC', 'SV',
                ];

                $convertedPrices = [];
                foreach ($result['data']['convertedRegionPrices'] as $regionCode => $priceData) {
                    // Skip known non-billable regions
                    if (in_array($regionCode, $knownNonBillableRegions)) {
                        continue;
                    }

                    $units = (int) $priceData['price']['units'];
                    $nanos = (int) ($priceData['price']['nanos'] ?? 0);
                    $priceMicros = $units * 1000000 + (int) ($nanos / 1000);

                    $convertedPrices[$regionCode] = [
                        'currency' => $priceData['price']['currencyCode'],
                        'priceMicros' => (string) $priceMicros,
                    ];
                }

                $this->logInfo('Using filtered regions from convertRegionPrices API', [
                    'total_regions' => count($convertedPrices),
                    'excluded_regions' => count($knownNonBillableRegions),
                    'regions' => array_keys($convertedPrices),
                ]);

                return $convertedPrices;
            }
        } catch (Exception $e) {
            $this->logWarning('Failed to convert region prices via API, falling back to manual calculation', [
                'error' => $e->getMessage(),
                'price_micros' => $priceMicros,
                'currency' => $currencyCode,
            ]);
        }

        // Fallback to manual calculation if API fails
        $this->logInfo('Using manual regional pricing as fallback', [
            'price_micros' => $priceMicros,
            'currency' => $currencyCode,
        ]);

        return $this->prepareRegionalPricesManual($googlePlayPlanData);
    }

    /**
     * Manual regional price calculation as fallback.
     * Uses comprehensive list of regions to ensure coverage.
     */
    private function prepareRegionalPricesManual(array $googlePlayPlanData): array
    {
        $priceMicros = $googlePlayPlanData['price_micros'];
        $currencyCode = $googlePlayPlanData['currency_code'];

        // Convert USD price to other currencies (approximate conversion for other regions)
        $usdPrice = $priceMicros / 1000000;

        return [
            // US and English-speaking
            'US' => ['currency' => $currencyCode, 'priceMicros' => (string) $priceMicros],
            'GB' => ['currency' => 'GBP', 'priceMicros' => (string) max(990000, round($usdPrice * 0.79 * 1000000))],
            'CA' => ['currency' => 'CAD', 'priceMicros' => (string) max(990000, round($usdPrice * 1.35 * 1000000))],
            'AU' => ['currency' => 'AUD', 'priceMicros' => (string) max(990000, round($usdPrice * 1.50 * 1000000))],
            'NZ' => ['currency' => 'NZD', 'priceMicros' => (string) max(990000, round($usdPrice * 1.65 * 1000000))],
            'IE' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],

            // Europe - Eurozone
            'AT' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'BE' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'DE' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'ES' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'FI' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'FR' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'GR' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'IT' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'LU' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'NL' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'PT' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'SK' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],
            'SI' => ['currency' => 'EUR', 'priceMicros' => (string) max(990000, round($usdPrice * 0.93 * 1000000))],

            // Europe - Non-Eurozone
            'CH' => ['currency' => 'CHF', 'priceMicros' => (string) max(990000, round($usdPrice * 0.9 * 1000000))],
            'CZ' => ['currency' => 'CZK', 'priceMicros' => (string) max(23000000, round($usdPrice * 23 * 1000000))],
            'DK' => ['currency' => 'DKK', 'priceMicros' => (string) max(6900000, round($usdPrice * 7 * 1000000))],
            'HU' => ['currency' => 'HUF', 'priceMicros' => (string) max(360000000, round($usdPrice * 360 * 1000000))],
            'NO' => ['currency' => 'NOK', 'priceMicros' => (string) max(9900000, round($usdPrice * 11 * 1000000))],
            'PL' => ['currency' => 'PLN', 'priceMicros' => (string) max(3990000, round($usdPrice * 4 * 1000000))],
            'RO' => ['currency' => 'RON', 'priceMicros' => (string) max(4500000, round($usdPrice * 4.5 * 1000000))],
            'SE' => ['currency' => 'SEK', 'priceMicros' => (string) max(9900000, round($usdPrice * 11 * 1000000))],
            'TR' => ['currency' => 'TRY', 'priceMicros' => (string) max(29000000, round($usdPrice * 29 * 1000000))],
            'UA' => ['currency' => 'UAH', 'priceMicros' => (string) max(36000000, round($usdPrice * 37 * 1000000))],

            // Asia Pacific
            'JP' => ['currency' => 'JPY', 'priceMicros' => (string) max(99000000, round($usdPrice * 150 * 1000000))],
            'KR' => ['currency' => 'KRW', 'priceMicros' => (string) max(1190000000, round($usdPrice * 1300 * 1000000))],
            'IN' => ['currency' => 'INR', 'priceMicros' => (string) max(75000000, round($usdPrice * 83 * 1000000))],
            'SG' => ['currency' => 'SGD', 'priceMicros' => (string) max(1350000, round($usdPrice * 1.35 * 1000000))],
            'HK' => ['currency' => 'HKD', 'priceMicros' => (string) max(7800000, round($usdPrice * 7.8 * 1000000))],
            'TW' => ['currency' => 'TWD', 'priceMicros' => (string) max(31000000, round($usdPrice * 31 * 1000000))],
            'MY' => ['currency' => 'MYR', 'priceMicros' => (string) max(4500000, round($usdPrice * 4.5 * 1000000))],
            'TH' => ['currency' => 'THB', 'priceMicros' => (string) max(35000000, round($usdPrice * 35 * 1000000))],
            'VN' => ['currency' => 'VND', 'priceMicros' => (string) max(23000000000, round($usdPrice * 24000 * 1000000))],
            'PH' => ['currency' => 'PHP', 'priceMicros' => (string) max(56000000, round($usdPrice * 56 * 1000000))],
            'ID' => ['currency' => 'IDR', 'priceMicros' => (string) max(15000000000, round($usdPrice * 15000 * 1000000))],

            // Americas
            'BR' => ['currency' => 'BRL', 'priceMicros' => (string) max(1990000, round($usdPrice * 5.2 * 1000000))],
            'MX' => ['currency' => 'MXN', 'priceMicros' => (string) max(17000000, round($usdPrice * 18 * 1000000))],
            'CL' => ['currency' => 'CLP', 'priceMicros' => (string) max(800000000, round($usdPrice * 850 * 1000000))],
            'CO' => ['currency' => 'COP', 'priceMicros' => (string) max(4000000000, round($usdPrice * 4200 * 1000000))],
            'PE' => ['currency' => 'PEN', 'priceMicros' => (string) max(3600000, round($usdPrice * 3.7 * 1000000))],

            // Middle East & Africa
            'IL' => ['currency' => 'ILS', 'priceMicros' => (string) max(3600000, round($usdPrice * 3.6 * 1000000))],
            'ZA' => ['currency' => 'ZAR', 'priceMicros' => (string) max(18000000, round($usdPrice * 18 * 1000000))],
            'AE' => ['currency' => 'AED', 'priceMicros' => (string) max(3670000, round($usdPrice * 3.67 * 1000000))],
            'SA' => ['currency' => 'SAR', 'priceMicros' => (string) max(3750000, round($usdPrice * 3.75 * 1000000))],
            'EG' => ['currency' => 'EGP', 'priceMicros' => (string) max(31000000, round($usdPrice * 31 * 1000000))],
        ];
    }
}
