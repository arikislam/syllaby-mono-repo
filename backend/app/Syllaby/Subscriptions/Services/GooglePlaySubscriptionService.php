<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;
use DateInterval;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use App\Syllaby\Subscriptions\GooglePlaySubscription;
use App\Syllaby\Subscriptions\Enums\GooglePlaySubscriptionNotification;
use App\Syllaby\Subscriptions\Contracts\GooglePlaySubscriptionServiceInterface;

/**
 * Google Play Subscription Service for managing subscription products
 */
class GooglePlaySubscriptionService extends GooglePlayBaseService implements GooglePlaySubscriptionServiceInterface
{
    public function sync(mixed $plan, bool $force = false, bool $isTest = false, bool $isFake = false): array
    {
        if (! $plan instanceof Plan) {
            return ['success' => false, 'error' => 'Invalid plan type'];
        }

        return $this->pushSubscription($plan, $force, $isTest, $isFake);
    }

    public function syncAll(bool $isTest = false, bool $isFake = false, bool $force = false): array
    {
        $query = Plan::whereIn('type', ['month', 'year'])->with('googlePlayPlan');

        if (! $force) {
            $query->needsGooglePlaySync();
        }

        $plans = $query->get();

        return $this->processPlansBatch($plans, $force, $isTest, $isFake);
    }

    public function generateBasePlanId(Plan $plan): string
    {
        return $plan->type === 'month' ? 'monthly' : 'yearly';
    }

    public function fetchAllSubscriptions(): array
    {
        return $this->fetchFromEndpoint('/subscriptions', 'fetch_all_subscriptions', 'subscriptions');
    }

    public function getSubscription(string $productId): array
    {
        try {
            $this->logInfo('Attempting to get subscription via list', ['product_id' => $productId]);

            $allSubscriptions = $this->fetchAllSubscriptions();

            foreach ($allSubscriptions as $subscription) {
                if (($subscription['productId'] ?? '') === $productId) {
                    $this->logInfo('Found subscription in list', [
                        'product_id' => $productId,
                        'base_plans_count' => count($subscription['basePlans'] ?? []),
                    ]);

                    return $subscription;
                }
            }

            $this->logInfo('Subscription not found in list', [
                'product_id' => $productId,
                'total_subscriptions' => count($allSubscriptions),
            ]);

            return [];
        } catch (Exception $e) {
            $this->logError('Exception getting subscription', [
                'product_id' => $productId,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function deleteSubscription(string $productId): array
    {
        try {
            return $this->makeApiRequest('delete', "/subscriptions/{$productId}", [], [
                'context' => 'delete_subscription',
                'product_id' => $productId,
            ]);
        } catch (Exception $e) {
            return $this->handleApiException($e, 'delete_subscription', ['product_id' => $productId]);
        }
    }

    public function deactivateSubscriptionBasePlan(string $productId, string $basePlanId): array
    {
        try {
            $endpoint = "/subscriptions/{$productId}/basePlans/{$basePlanId}:deactivate";

            return $this->makeApiRequest('post', $endpoint, [], [
                'context' => 'deactivate_base_plan',
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
            ]);
        } catch (Exception $e) {
            return $this->handleApiException($e, 'deactivate_base_plan', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
            ]);
        }
    }

    public function activateSubscriptionBasePlan(string $productId, string $basePlanId): array
    {
        try {
            $endpoint = "/subscriptions/{$productId}/basePlans/{$basePlanId}:activate";

            return $this->makeApiRequest('post', $endpoint, [], [
                'context' => 'activate_base_plan',
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
            ]);
        } catch (Exception $e) {
            return $this->handleApiException($e, 'activate_base_plan', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
            ]);
        }
    }

    /**
     * Verify a subscription purchase token with Google Play API.
     *
     * @param  string  $purchaseToken  The purchase token to verify
     * @param  string  $productId  The subscription product ID
     * @return array{verified: bool, data: array|null, error: string|null}
     */
    public function verifyPurchaseToken(string $purchaseToken, string $productId): array
    {
        try {
            $endpoint = "/purchases/subscriptions/{$productId}/tokens/{$purchaseToken}";
            $result = $this->makeApiRequest('get', $endpoint, [], [
                'purchase_token' => $purchaseToken,
                'product_id' => $productId,
                'type' => 'subscription',
            ]);

            if ($result['success'] && isset($result['data'])) {
                $this->logDebug('Subscription purchase token verified successfully', [
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
            $this->logError('Exception verifying subscription purchase token', [
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
     * Map Google Play subscription status based on API data.
     *
     * @param  array  $apiData  The API response data
     * @param  string|null  $notificationType  Optional notification type for context
     * @return string The mapped status
     */
    public function mapSubscriptionStatus(array $apiData, ?string $notificationType = null): string
    {
        if ($notificationType) {
            $notification = GooglePlaySubscriptionNotification::fromString($notificationType);

            if ($notification) {
                switch ($notification) {
                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_CANCELED:
                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_EXPIRED:
                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_REVOKED:
                        return GooglePlaySubscription::STATUS_EXPIRED;

                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_PAUSED:
                        return GooglePlaySubscription::STATUS_PAUSED;

                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_IN_GRACE_PERIOD:
                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_ON_HOLD:
                        return GooglePlaySubscription::STATUS_IN_GRACE_PERIOD;

                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_RECOVERED:
                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_RESTARTED:
                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_RENEWED:
                    case GooglePlaySubscriptionNotification::SUBSCRIPTION_PURCHASED:
                        return GooglePlaySubscription::STATUS_ACTIVE;
                }
            }
        }

        if (isset($apiData['userCancellationTimeMillis'])) {
            return GooglePlaySubscription::STATUS_CANCELED;
        }

        if (isset($apiData['paymentState']) && $apiData['paymentState'] === 0) {
            return GooglePlaySubscription::STATUS_IN_GRACE_PERIOD;
        }

        if (isset($apiData['expiryTimeMillis'])) {
            $expiryTime = Carbon::createFromTimestampMs($apiData['expiryTimeMillis']);
            if ($expiryTime->isPast()) {
                return GooglePlaySubscription::STATUS_EXPIRED;
            }
        }

        if (isset($apiData['pausedStateContext'])) {
            return GooglePlaySubscription::STATUS_PAUSED;
        }

        return GooglePlaySubscription::STATUS_ACTIVE;
    }

    /**
     * Build subscription data array from RTDN and API response.
     *
     * @param  GooglePlayRtdn  $rtdn  The RTDN record
     * @param  array  $apiData  The Google Play API response data
     * @return array The formatted subscription data
     */
    public function buildSubscriptionData(GooglePlayRtdn $rtdn, array $apiData): array
    {
        $priceAmountMicros = $apiData['priceAmountMicros'] ?? $rtdn->plan->googlePlayPlan->price_micros ?? 0;
        $priceCurrencyCode = isset($apiData['priceAmountMicros'])
            ? ($apiData['priceCurrencyCode'] ?? 'USD')
            : ($rtdn->plan->googlePlayPlan->currency_code ?? 'USD');

        $offerInfo = $this->extractCompleteOfferInformation($apiData);
        $trialInfo = $this->extractTrialPeriodInfo($apiData, $offerInfo);

        $data = [
            'user_id' => $rtdn->user_id,
            'subscription_id' => $apiData['orderId'] ?? null,
            'linked_purchase_token' => $apiData['linkedPurchaseToken'] ?? null,
            'plan_id' => $rtdn->plan_id,
            'product_id' => $rtdn->plan->googlePlayPlan->product_id,
            'offer_id' => $offerInfo['offer_id'],
            'status' => $this->mapSubscriptionStatus($apiData, $rtdn->notification_type),
            'free_trial_period' => null,
            'intro_price_info' => null,
            'started_at' => $this->parseTimestamp($apiData['startTimeMillis'] ?? null),
            'expires_at' => $this->parseTimestamp($apiData['expiryTimeMillis'] ?? null),
            'trial_start_at' => null,
            'trial_end_at' => null,
            'auto_renewing' => $apiData['autoRenewing'] ?? false,
            'payment_state' => $apiData['paymentState'] ?? null,
            'price_currency_code' => $priceCurrencyCode,
            'price_amount_micros' => $priceAmountMicros,
            'country_code' => $apiData['countryCode'] ?? null,
            'acknowledgement_state' => $apiData['acknowledgementState'] ?? null,
            'metadata' => $this->buildMetadata($apiData),
        ];

        if ($trialInfo) {
            $data['free_trial_period'] = $trialInfo['period_data'];
            $data['trial_start_at'] = $trialInfo['trial_start_at'];
            $data['trial_end_at'] = $trialInfo['trial_end_at'];

            $this->logInfo('Trial period information added to subscription', [
                'offer_id' => $offerInfo['offer_id'],
                'trial_period_source' => $trialInfo['period_data']['source'] ?? 'unknown',
                'trial_start_at' => $trialInfo['trial_start_at']?->toIso8601String(),
                'trial_end_at' => $trialInfo['trial_end_at']?->toIso8601String(),
            ]);
        }

        if (isset($apiData['introductoryPriceInfo'])) {
            $data['intro_price_info'] = $apiData['introductoryPriceInfo'];
        }

        return $data;
    }

    /**
     * Extract complete offer information from Google Play API response.
     *
     * @param  array  $purchaseData  The API response data
     * @return array The extracted offer information
     */
    public function extractCompleteOfferInformation(array $purchaseData): array
    {
        $offerInfo = [
            'offer_id' => null,
            'base_plan_id' => null,
            'offer_tags' => [],
            'promotion_code' => null,
            'promotion_type' => null,
            'is_trial_offer' => false,
        ];

        if (isset($purchaseData['lineItems'])) {
            foreach ($purchaseData['lineItems'] as $lineItem) {
                if (isset($lineItem['offerDetails'])) {
                    $offerDetails = $lineItem['offerDetails'];
                    $offerInfo['offer_id'] = $offerDetails['offerId'] ?? null;
                    $offerInfo['base_plan_id'] = $offerDetails['basePlanId'] ?? null;
                    $offerInfo['offer_tags'] = $offerDetails['offerTags'] ?? [];
                }

                if (isset($lineItem['signupPromotion'])) {
                    $promo = $lineItem['signupPromotion'];
                    if (isset($promo['vanityCode']['promotionCode'])) {
                        $offerInfo['promotion_code'] = $promo['vanityCode']['promotionCode'];
                        $offerInfo['promotion_type'] = 'vanity_code';
                    } elseif (isset($promo['oneTimeCode'])) {
                        $offerInfo['promotion_type'] = 'one_time_code';
                    }
                }
            }
        }

        if (isset($purchaseData['promotionCode'])) {
            $offerInfo['promotion_code'] = $purchaseData['promotionCode'];
        }

        if (isset($purchaseData['promotionType'])) {
            $offerInfo['promotion_type'] = (int) $purchaseData['promotionType'];
        }

        return $offerInfo;
    }

    /**
     * Extract trial period information and calculate trial dates.
     *
     * @param  array  $apiData  The API response data
     * @param  array  $offerInfo  The offer information
     * @return array|null The trial period information or null if not a trial
     */
    private function extractTrialPeriodInfo(array $apiData, array $offerInfo = []): ?array
    {
        $trialPeriod = null;
        $subscriptionStartTime = null;
        $productId = null;

        if (isset($apiData['startTimeMillis'])) {
            $subscriptionStartTime = Carbon::createFromTimestampMs($apiData['startTimeMillis']);
        } elseif (isset($apiData['startTime'])) {
            $subscriptionStartTime = Carbon::parse($apiData['startTime']);
        }

        if (isset($apiData['paymentState']) && $apiData['paymentState'] === 2) {
            $trialPeriod = [
                'payment_state' => 2,
                'source' => 'payment_state',
            ];

            if (! empty($offerInfo['offer_id']) && ! empty($offerInfo['base_plan_id'])) {
                $trialPeriod['needs_offer_details'] = true;
                $trialPeriod['offer_id'] = $offerInfo['offer_id'];
                $trialPeriod['base_plan_id'] = $offerInfo['base_plan_id'];

                if (isset($apiData['lineItems'][0]['productId'])) {
                    $trialPeriod['product_id'] = $apiData['lineItems'][0]['productId'];
                }
            }
        }

        if (! $trialPeriod) {
            return null;
        }

        $trialStartAt = $subscriptionStartTime;
        $trialEndAt = null;

        if ($subscriptionStartTime) {
            if (isset($apiData['expiryTimeMillis']) && isset($apiData['paymentState']) && $apiData['paymentState'] === 2) {
                $trialEndAt = Carbon::createFromTimestampMs($apiData['expiryTimeMillis']);
            } elseif (isset($apiData['lineItems'][0]['expiryTime']) && isset($apiData['paymentState']) && $apiData['paymentState'] === 2) {
                $trialEndAt = Carbon::parse($apiData['lineItems'][0]['expiryTime']);
            } elseif (isset($trialPeriod['period'])) {
                $trialEndAt = $this->calculateTrialEndDate($subscriptionStartTime, $trialPeriod);
            }
        }

        return [
            'period_data' => $trialPeriod,
            'trial_start_at' => $trialStartAt,
            'trial_end_at' => $trialEndAt,
        ];
    }

    /**
     * Calculate trial end date based on ISO 8601 period format.
     *
     * @param  Carbon  $startDate  The start date
     * @param  array  $trialPeriod  The trial period information
     * @return Carbon|null The calculated end date
     */
    private function calculateTrialEndDate(Carbon $startDate, array $trialPeriod): ?Carbon
    {
        if (! isset($trialPeriod['period'])) {
            return null;
        }

        $period = $trialPeriod['period'];
        $cycles = $trialPeriod['cycles'] ?? 1;

        try {
            $interval = new DateInterval($period);
            $endDate = clone $startDate;

            for ($i = 0; $i < $cycles; $i++) {
                $endDate->add($interval);
            }

            return $endDate;
        } catch (Exception $e) {
            $this->logWarning('Failed to parse trial period', [
                'period' => $period,
                'cycles' => $cycles,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build metadata array.
     *
     * @param  array  $apiData  The API response data
     * @return array The metadata
     */
    private function buildMetadata(array $apiData): array
    {
        return [
            'obfuscated_external_account_id' => $apiData['obfuscatedExternalAccountId'] ?? null,
            'profile_id' => $apiData['profileId'] ?? null,
        ];
    }

    /**
     * Parse timestamp from milliseconds.
     *
     * @param  string|null  $timestampMs  The timestamp in milliseconds
     * @return Carbon|null The parsed timestamp
     */
    private function parseTimestamp(?string $timestampMs): ?Carbon
    {
        return $timestampMs ? Carbon::createFromTimestampMs($timestampMs) : null;
    }

    /**
     * Enhance trial period data with offer details.
     * This method is called by the verification service which has access to offer service.
     *
     * @param  array  $trialPeriodData  The trial period data to enhance
     * @param  array  $offerDetails  The offer details from Google Play API
     * @return array The enhanced trial period data
     */
    public function enhanceTrialPeriodWithOfferDetails(array $trialPeriodData, array $offerDetails): array
    {
        if (! empty($offerDetails['trial_duration'])) {
            $trialPeriodData['period'] = $offerDetails['trial_duration'];
            $trialPeriodData['cycles'] = $offerDetails['trial_cycles'] ?? 1;
            $trialPeriodData['offer_details'] = $offerDetails;
            $trialPeriodData['source'] = 'payment_state_with_offer_api';
        }

        return $trialPeriodData;
    }

    private function pushSubscription(Plan $plan, bool $force, bool $isTest = false, bool $isFake = false): array
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
            $result = $this->createOrUpdateSubscriptionWithData($plan, $googlePlayPlanData, $googlePlayPlan, $force, $isTest);

            if ($result['success']) {
                // Only create GooglePlayPlan after successful API call
                if ($createdNewGooglePlayPlan) {
                    $googlePlayPlan = GooglePlayPlan::create($googlePlayPlanData);
                }

                // Save the response data and handle activation
                $this->saveSubscriptionResponse($googlePlayPlan, $result['data'], $isTest);
                $this->autoActivateIfNeeded($result['data'], $googlePlayPlan, $isTest);

                return [
                    'success' => true,
                    'plan_id' => $plan->id,
                    'product_id' => $googlePlayPlanData['product_id'],
                    'action' => $result['action'] ?? 'created_or_updated',
                    'data' => $result['data'],
                ];
            } else {
                // API call failed - don't create GooglePlayPlan
                return [
                    'success' => false,
                    'plan_id' => $plan->id,
                    'product_id' => $googlePlayPlanData['product_id'],
                    'action' => 'failed',
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

            return $this->handleApiException($e, 'push_subscription', ['plan_id' => $plan->id]);
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
        $basePlanId = $this->generateBasePlanId($plan);
        $billingPeriod = $plan->type === 'month' ? 'P1M' : 'P1Y';

        return [
            'product_id' => $this->generateProductSku($plan),
            'product_type' => 'subscription',
            'name' => $plan->name,
            'status' => $isTest ? 'inactive' : 'active',
            'plan_id' => $plan->id,
            'price_micros' => $computedPriceMicros,
            'currency_code' => strtoupper($plan->currency ?? 'USD'),
            'base_plan_id' => $basePlanId,
            'billing_period' => $billingPeriod,
        ];
    }

    private function generateProductSku(Plan $plan): string
    {
        return $this->generateUniqueId($plan->type, $plan->name);
    }

    private function createOrUpdateSubscriptionWithData(Plan $plan, array $googlePlayPlanData, ?GooglePlayPlan $googlePlayPlan, bool $force, bool $isTest): array
    {
        $subscriptionData = $this->prepareSubscriptionDataFromArray($plan, $googlePlayPlanData, $isTest);

        $queryParams = [
            'productId' => $googlePlayPlanData['product_id'],
            'regionsVersion.version' => '2022/02',
        ];

        if ($force) {
            $existingSubscription = $this->getSubscription($googlePlayPlanData['product_id']);
            if (! empty($existingSubscription)) {
                return $this->handleExistingSubscriptionWithData($existingSubscription, $plan, $googlePlayPlanData, $subscriptionData, $isTest);
            }
        }

        $result = $this->makeApiRequest('post', '/subscriptions', $subscriptionData, [
            'context' => 'create_subscription',
            'plan_id' => $plan->id,
            'queryParams' => $queryParams,
        ]);

        if (! $result['success'] && $result['status'] === 409) {
            $result = $this->updateSubscriptionWithData($googlePlayPlanData, $subscriptionData, $plan->id);
        }

        return [
            'success' => $result['success'],
            'plan_id' => $plan->id,
            'product_id' => $googlePlayPlanData['product_id'],
            'action' => $result['success'] ? 'created_or_updated' : 'failed',
            'data' => $result['data'] ?? [],
            'error' => $result['error'] ?? null,
        ];
    }

    private function createOrUpdateSubscription(Plan $plan, GooglePlayPlan $googlePlayPlan, bool $force, bool $isTest): array
    {
        $subscriptionData = $this->prepareSubscriptionData($plan, $googlePlayPlan, $isTest);

        $queryParams = [
            'productId' => $googlePlayPlan->product_id,
            'regionsVersion.version' => '2022/02',
        ];

        if ($force) {
            $existingSubscription = $this->getSubscription($googlePlayPlan->product_id);
            if (! empty($existingSubscription)) {
                return $this->handleExistingSubscription($existingSubscription, $plan, $googlePlayPlan, $subscriptionData, $isTest);
            }
        }

        $result = $this->makeApiRequest('post', '/subscriptions', $subscriptionData, [
            'context' => 'create_subscription',
            'plan_id' => $plan->id,
            'queryParams' => $queryParams,
        ]);

        if (! $result['success'] && $result['status'] === 409) {
            $result = $this->updateSubscription($googlePlayPlan, $subscriptionData, $plan->id);
        }

        if ($result['success']) {
            $this->saveSubscriptionResponse($googlePlayPlan, $result['data'], $isTest);
            $this->autoActivateIfNeeded($result['data'], $googlePlayPlan, $isTest);
        }

        return [
            'success' => $result['success'],
            'plan_id' => $plan->id,
            'product_id' => $googlePlayPlan->product_id,
            'action' => $result['success'] ? 'created_or_updated' : 'failed',
            'data' => $result['data'] ?? [],
        ];
    }

    private function handleExistingSubscription(array $existingSubscription, Plan $plan, GooglePlayPlan $googlePlayPlan, array $subscriptionData, bool $isTest): array
    {
        $basePlans = $existingSubscription['basePlans'] ?? [];
        $basePlanId = $googlePlayPlan->base_plan_id;

        foreach ($basePlans as $basePlan) {
            if ($basePlan['basePlanId'] === $basePlanId) {
                $currentState = $basePlan['state'] ?? '';

                $this->logInfo('Found matching base plan', [
                    'product_id' => $googlePlayPlan->product_id,
                    'base_plan_id' => $basePlanId,
                    'current_state' => $currentState,
                ]);

                if (in_array($currentState, ['DRAFT', 'INACTIVE'])) {
                    $activationResult = $this->activateSubscriptionBasePlan($googlePlayPlan->product_id, $basePlanId);

                    if ($activationResult['success']) {
                        $this->logInfo('Base plan activated', [
                            'product_id' => $googlePlayPlan->product_id,
                            'base_plan_id' => $basePlanId,
                            'previous_state' => $currentState,
                        ]);

                        $googlePlayPlan->update(['status' => 'ACTIVE']);
                        $googlePlayPlan->markAsSynced();

                        return [
                            'success' => true,
                            'plan_id' => $plan->id,
                            'product_id' => $googlePlayPlan->product_id,
                            'action' => 'activated',
                            'data' => $activationResult['data'] ?? [],
                        ];
                    }
                } else {
                    $result = $this->updateSubscription($googlePlayPlan, $subscriptionData, $plan->id);

                    if ($result['success']) {
                        $this->saveSubscriptionResponse($googlePlayPlan, $result['data'], $isTest);
                        $this->autoActivateIfNeeded($result['data'], $googlePlayPlan, $isTest);

                        return [
                            'success' => true,
                            'plan_id' => $plan->id,
                            'product_id' => $googlePlayPlan->product_id,
                            'action' => 'updated',
                            'data' => $result['data'] ?? [],
                        ];
                    }
                }
                break;
            }
        }

        return [
            'success' => false,
            'plan_id' => $plan->id,
            'product_id' => $googlePlayPlan->product_id,
            'action' => 'failed',
            'error' => 'Base plan not found in existing subscription',
        ];
    }

    private function updateSubscription(GooglePlayPlan $googlePlayPlan, array $subscriptionData, int $planId): array
    {
        $patchQueryParams = [
            'updateMask' => 'basePlans,listings',
            'regionsVersion.version' => '2022/02',
        ];

        return $this->makeApiRequest('patch', "/subscriptions/{$googlePlayPlan->product_id}", $subscriptionData, [
            'context' => 'update_subscription',
            'plan_id' => $planId,
            'queryParams' => $patchQueryParams,
        ]);
    }

    private function autoActivateIfNeeded(array $responseData, GooglePlayPlan $googlePlayPlan, bool $isTest): void
    {
        if ($isTest) {
            return;
        }

        $firstBasePlan = $responseData['basePlans'][0] ?? [];
        $currentState = $firstBasePlan['state'] ?? null;

        if (in_array($currentState, ['DRAFT', 'INACTIVE'], true)) {
            $activation = $this->activateSubscriptionBasePlan($googlePlayPlan->product_id, $googlePlayPlan->base_plan_id);
            if ($activation['success']) {
                $googlePlayPlan->update(['status' => 'ACTIVE']);
            }
        }
    }

    private function prepareSubscriptionDataFromArray(Plan $plan, array $googlePlayPlanData, bool $isTest): array
    {
        $basePlanId = $googlePlayPlanData['base_plan_id'];
        $billingPeriod = $googlePlayPlanData['billing_period'];

        $existingSubscription = $this->getSubscription($googlePlayPlanData['product_id']);
        $existingBasePlans = $existingSubscription['basePlans'] ?? [];
        $existingRegionalConfigs = [];

        foreach ($existingBasePlans as $existingBasePlan) {
            if ($existingBasePlan['basePlanId'] === $basePlanId) {
                $existingRegionalConfigs = $existingBasePlan['regionalConfigs'] ?? [];
                break;
            }
        }

        $regionalConfigs = $this->prepareRegionalConfigsFromArray($existingRegionalConfigs, $googlePlayPlanData, $isTest);

        $usdPrice = $googlePlayPlanData['price_micros'] / 1000000;
        $eurPrice = max(1, round($usdPrice * 0.93));

        return [
            'packageName' => $this->packageName,
            'basePlans' => [
                [
                    'basePlanId' => $basePlanId,
                    'state' => $isTest ? 'DRAFT' : 'ACTIVE',
                    'autoRenewingBasePlanType' => [
                        'billingPeriodDuration' => $billingPeriod,
                        'legacyCompatible' => true,
                    ],
                    'regionalConfigs' => $regionalConfigs,
                    'otherRegionsConfig' => [
                        'newSubscriberAvailability' => ! $isTest,
                        'usdPrice' => [
                            'currencyCode' => 'USD',
                            'units' => (string) floor($googlePlayPlanData['price_micros'] / 1000000),
                            'nanos' => ($googlePlayPlanData['price_micros'] % 1000000) * 1000,
                        ],
                        'eurPrice' => [
                            'currencyCode' => 'EUR',
                            'units' => (string) $eurPrice,
                            'nanos' => 0,
                        ],
                    ],
                ],
            ],
            'listings' => [
                [
                    'languageCode' => 'en-US',
                    'title' => $googlePlayPlanData['name'],
                    'description' => "Subscription for Syllaby platform. {$plan->name}",
                ],
            ],
        ];
    }

    private function handleExistingSubscriptionWithData(array $existingSubscription, Plan $plan, array $googlePlayPlanData, array $subscriptionData, bool $isTest): array
    {
        $basePlans = $existingSubscription['basePlans'] ?? [];
        $basePlanId = $googlePlayPlanData['base_plan_id'];

        foreach ($basePlans as $basePlan) {
            if ($basePlan['basePlanId'] === $basePlanId) {
                $currentState = $basePlan['state'] ?? '';

                $this->logInfo('Found matching base plan', [
                    'product_id' => $googlePlayPlanData['product_id'],
                    'base_plan_id' => $basePlanId,
                    'current_state' => $currentState,
                ]);

                if (in_array($currentState, ['DRAFT', 'INACTIVE'])) {
                    $activationResult = $this->activateSubscriptionBasePlan($googlePlayPlanData['product_id'], $basePlanId);

                    if ($activationResult['success']) {
                        $this->logInfo('Base plan activated', [
                            'product_id' => $googlePlayPlanData['product_id'],
                            'base_plan_id' => $basePlanId,
                            'previous_state' => $currentState,
                        ]);

                        return [
                            'success' => true,
                            'plan_id' => $plan->id,
                            'product_id' => $googlePlayPlanData['product_id'],
                            'action' => 'activated',
                            'data' => $activationResult['data'] ?? [],
                        ];
                    }
                } else {
                    $result = $this->updateSubscriptionWithData($googlePlayPlanData, $subscriptionData, $plan->id);

                    if ($result['success']) {
                        return [
                            'success' => true,
                            'plan_id' => $plan->id,
                            'product_id' => $googlePlayPlanData['product_id'],
                            'action' => 'updated',
                            'data' => $result['data'] ?? [],
                        ];
                    }
                }
                break;
            }
        }

        return [
            'success' => false,
            'plan_id' => $plan->id,
            'product_id' => $googlePlayPlanData['product_id'],
            'action' => 'failed',
            'error' => 'Base plan not found in existing subscription',
        ];
    }

    private function updateSubscriptionWithData(array $googlePlayPlanData, array $subscriptionData, int $planId): array
    {
        $patchQueryParams = [
            'updateMask' => 'basePlans,listings',
            'regionsVersion.version' => '2022/02',
        ];

        return $this->makeApiRequest('patch', "/subscriptions/{$googlePlayPlanData['product_id']}", $subscriptionData, [
            'context' => 'update_subscription',
            'plan_id' => $planId,
            'queryParams' => $patchQueryParams,
        ]);
    }

    private function prepareRegionalConfigsFromArray(array $existingRegionalConfigs, array $googlePlayPlanData, bool $isTest): array
    {
        if (! empty($existingRegionalConfigs)) {
            $regionalConfigs = $existingRegionalConfigs;

            foreach ($regionalConfigs as &$config) {
                if ($config['regionCode'] === 'US') {
                    $config['price'] = [
                        'currencyCode' => $googlePlayPlanData['currency_code'],
                        'units' => (string) floor($googlePlayPlanData['price_micros'] / 1000000),
                        'nanos' => ($googlePlayPlanData['price_micros'] % 1000000) * 1000,
                    ];
                    break;
                }
            }

            return $regionalConfigs;
        }

        return [
            [
                'regionCode' => 'US',
                'newSubscriberAvailability' => ! $isTest,
                'price' => [
                    'currencyCode' => $googlePlayPlanData['currency_code'],
                    'units' => (string) floor($googlePlayPlanData['price_micros'] / 1000000),
                    'nanos' => ($googlePlayPlanData['price_micros'] % 1000000) * 1000,
                ],
            ],
        ];
    }

    private function prepareSubscriptionData(Plan $plan, GooglePlayPlan $googlePlayPlan, bool $isTest): array
    {
        $basePlanId = $googlePlayPlan->base_plan_id;
        $billingPeriod = $googlePlayPlan->billing_period;

        $existingSubscription = $this->getSubscription($googlePlayPlan->product_id);
        $existingBasePlans = $existingSubscription['basePlans'] ?? [];
        $existingRegionalConfigs = [];

        foreach ($existingBasePlans as $existingBasePlan) {
            if ($existingBasePlan['basePlanId'] === $basePlanId) {
                $existingRegionalConfigs = $existingBasePlan['regionalConfigs'] ?? [];
                break;
            }
        }

        $regionalConfigs = $this->prepareRegionalConfigs($existingRegionalConfigs, $googlePlayPlan, $isTest);

        $usdPrice = $googlePlayPlan->price_micros / 1000000;
        $eurPrice = max(1, round($usdPrice * 0.93));

        return [
            'packageName' => $this->packageName,
            'basePlans' => [
                [
                    'basePlanId' => $basePlanId,
                    'state' => $isTest ? 'DRAFT' : 'ACTIVE',
                    'autoRenewingBasePlanType' => [
                        'billingPeriodDuration' => $billingPeriod,
                        'legacyCompatible' => true,
                    ],
                    'regionalConfigs' => $regionalConfigs,
                    'otherRegionsConfig' => [
                        'newSubscriberAvailability' => ! $isTest,
                        'usdPrice' => [
                            'currencyCode' => 'USD',
                            'units' => (string) floor($googlePlayPlan->price_micros / 1000000),
                            'nanos' => ($googlePlayPlan->price_micros % 1000000) * 1000,
                        ],
                        'eurPrice' => [
                            'currencyCode' => 'EUR',
                            'units' => (string) $eurPrice,
                            'nanos' => 0,
                        ],
                    ],
                ],
            ],
            'listings' => [
                [
                    'languageCode' => 'en-US',
                    'title' => $googlePlayPlan->getGooglePlayTitle(),
                    'description' => $googlePlayPlan->getGooglePlayDescription() ?: "Subscription for Syllaby platform. {$plan->name}",
                ],
            ],
        ];
    }

    private function prepareRegionalConfigs(array $existingRegionalConfigs, GooglePlayPlan $googlePlayPlan, bool $isTest): array
    {
        if (! empty($existingRegionalConfigs)) {
            $regionalConfigs = $existingRegionalConfigs;

            foreach ($regionalConfigs as &$config) {
                if ($config['regionCode'] === 'US') {
                    $config['price'] = [
                        'currencyCode' => $googlePlayPlan->currency_code,
                        'units' => (string) floor($googlePlayPlan->price_micros / 1000000),
                        'nanos' => ($googlePlayPlan->price_micros % 1000000) * 1000,
                    ];
                    break;
                }
            }

            return $regionalConfigs;
        }

        return [
            [
                'regionCode' => 'US',
                'newSubscriberAvailability' => ! $isTest,
                'price' => [
                    'currencyCode' => $googlePlayPlan->currency_code,
                    'units' => (string) floor($googlePlayPlan->price_micros / 1000000),
                    'nanos' => ($googlePlayPlan->price_micros % 1000000) * 1000,
                ],
            ],
        ];
    }

    private function saveSubscriptionResponse(GooglePlayPlan $googlePlayPlan, array $responseData, bool $isTest): void
    {
        $basePlans = $responseData['basePlans'] ?? [];
        $firstBasePlan = $basePlans[0] ?? [];

        $googlePlayPlan->update([
            'status' => Arr::get($firstBasePlan, 'state', $isTest ? 'DRAFT' : 'ACTIVE'),
            'metadata' => $responseData,
        ]);

        $googlePlayPlan->markAsSynced();
    }

    private function handleFakeMode(Plan $plan, GooglePlayPlan $googlePlayPlan, bool $isTest): array
    {
        $googlePlayPlan->update(['status' => $isTest ? 'DRAFT' : 'ACTIVE']);
        $googlePlayPlan->markAsSynced();

        return [
            'success' => true,
            'status' => 'synced_locally',
            'plan_id' => $plan->id,
            'product_id' => $googlePlayPlan->product_id,
        ];
    }

    private function processPlansBatch(mixed $plans, bool $force, bool $isTest, bool $isFake): array
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
            $response = $this->pushSubscription($plan, $force, $isTest, $isFake);

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
}
