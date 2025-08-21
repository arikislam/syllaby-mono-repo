<?php

namespace App\Syllaby\Subscriptions\Services;

use Carbon\Carbon;
use DateInterval;
use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use App\Syllaby\Subscriptions\GooglePlayPurchase;
use App\Syllaby\Subscriptions\GooglePlaySubscription;
use App\Syllaby\Subscriptions\GooglePlaySubscriptionItem;
use App\Syllaby\Subscriptions\Contracts\GooglePlayApiClientInterface;

class GooglePlayVerificationService extends GooglePlayBaseService
{
    /**
     * Purchase types supported by Google Play.
     */
    private const TYPE_SUBSCRIPTION = 'subscription';

    private const TYPE_PRODUCT = 'product';

    /**
     * Grace period for subscription renewal (in hours).
     */
    private const RENEWAL_GRACE_PERIOD_HOURS = 24;

    /**
     * Service instances.
     */
    private GooglePlayAcknowledgementService $acknowledgementService;

    private GooglePlayOfferService $offerService;

    private GooglePlaySubscriptionService $subscriptionService;

    private GooglePlayProductService $productService;

    /**
     * Constructor
     */
    public function __construct(
        GooglePlayApiClientInterface $apiClient,
        GooglePlayPriceValidator $priceValidator,
        GooglePlayIdGenerator $idGenerator,
        GooglePlayAcknowledgementService $acknowledgementService,
        GooglePlayOfferService $offerService,
        GooglePlaySubscriptionService $subscriptionService,
        GooglePlayProductService $productService
    ) {
        parent::__construct($apiClient, $priceValidator, $idGenerator);
        $this->acknowledgementService = $acknowledgementService;
        $this->offerService = $offerService;
        $this->subscriptionService = $subscriptionService;
        $this->productService = $productService;
    }

    /**
     * Verify and save subscription data.
     */
    public function verifyAndSaveSubscription(GooglePlayRtdn $rtdn): bool
    {
        return $this->verifyAndSave($rtdn, self::TYPE_SUBSCRIPTION);
    }

    /**
     * Verify and save one-time purchase data.
     */
    public function verifyAndSavePurchase(GooglePlayRtdn $rtdn): bool
    {
        return $this->verifyAndSave($rtdn, self::TYPE_PRODUCT);
    }

    /**
     * Save subscription data from existing google_api_response.
     */
    public function saveSubscriptionFromExistingData(GooglePlayRtdn $rtdn): void
    {
        if (! $rtdn->google_api_response) {
            $this->logWarning('Cannot save subscription data - no google_api_response available', [
                'rtdn_id' => $rtdn->id,
            ]);

            return;
        }

        $this->saveSubscriptionData($rtdn, $rtdn->google_api_response);
    }

    /**
     * Save purchase data from existing google_api_response.
     */
    public function savePurchaseFromExistingData(GooglePlayRtdn $rtdn): void
    {
        if (! $rtdn->google_api_response) {
            $this->logWarning('Cannot save purchase data - no google_api_response available', [
                'rtdn_id' => $rtdn->id,
            ]);

            return;
        }

        $this->savePurchaseData($rtdn, $rtdn->google_api_response);
    }

    /**
     * Verify a purchase token with Google Play API.
     *
     * @return array{verified: bool, data: array|null, error: string|null}
     */
    public function verifyPurchaseToken(string $purchaseToken, string $productId, string $type = self::TYPE_SUBSCRIPTION): array
    {
        // Delegate to the appropriate service
        if ($type === self::TYPE_SUBSCRIPTION) {
            return $this->subscriptionService->verifyPurchaseToken($purchaseToken, $productId);
        } else {
            return $this->productService->verifyPurchaseToken($purchaseToken, $productId);
        }
    }

    /**
     * Map subscription status (delegates to subscription service).
     */
    public function mapSubscriptionStatus(array $apiData, ?string $notificationType = null): string
    {
        return $this->subscriptionService->mapSubscriptionStatus($apiData, $notificationType);
    }

    /**
     * Map purchase status (delegates to product service).
     */
    public function mapPurchaseStatus(array $apiData): string
    {
        return $this->productService->mapPurchaseStatus($apiData);
    }

    /**
     * Try to identify user from Google Play purchase data.
     *
     * @return array{user_id: int|null, method: string|null}
     */
    public function identifyUser(array $googleData): array
    {
        // Try email identification first
        $userByEmail = $this->identifyUserByEmail($googleData);
        if ($userByEmail['user_id']) {
            return $userByEmail;
        }

        // Fallback to obfuscated account ID
        return $this->identifyUserByObfuscatedId($googleData);
    }

    /**
     * Check if subscription needs renewal based on expiry time.
     */
    public function needsRenewal(GooglePlaySubscription $subscription): bool
    {
        if (! $subscription->expires_at) {
            return false;
        }

        return $subscription->expires_at->lessThan(now()->addHours(self::RENEWAL_GRACE_PERIOD_HOURS));
    }

    /**
     * Save subscription data to GooglePlaySubscription and GooglePlaySubscriptionItem.
     */
    protected function saveSubscriptionData(GooglePlayRtdn $rtdn, array $apiData): void
    {
        if (! $this->canSaveData($rtdn)) {
            return;
        }

        // Wrap subscription creation in transaction for atomicity
        DB::transaction(function () use ($rtdn, $apiData) {
            $subscription = $this->upsertSubscription($rtdn, $apiData);
            $this->handleLinkedPurchaseToken($subscription->linked_purchase_token);
            $this->handleSubscriptionRenewal($rtdn, $subscription);
            $this->updateSubscriptionCancellation($subscription, $apiData);
            $this->upsertSubscriptionItem($subscription, $rtdn, $apiData);

            // Delegate acknowledgement to the acknowledgement service
            $isRenewal = $rtdn->notification_type === 'subscription.renewed';
            $this->acknowledgementService->acknowledgeSubscriptionIfNeeded($rtdn, $subscription, $apiData, $isRenewal);

            $this->logSubscriptionSaved($subscription, $rtdn);
        });
    }

    /**
     * Save one-time purchase data to GooglePlayPurchase.
     */
    protected function savePurchaseData(GooglePlayRtdn $rtdn, array $apiData): void
    {
        if (! $this->canSaveData($rtdn)) {
            return;
        }

        // Wrap purchase creation in transaction for atomicity
        DB::transaction(function () use ($rtdn, $apiData) {
            $purchase = $this->upsertPurchase($rtdn, $apiData);

            // Handle acknowledgement based on API data
            $this->acknowledgementService->updatePurchaseAcknowledgement($purchase, $apiData);

            $this->logInfo('Google Play purchase data saved', [
                'purchase_id' => $purchase->id,
                'rtdn_id' => $rtdn->id,
                'status' => $purchase->status,
            ]);
        });
    }

    /**
     * Common verification and save logic.
     */
    private function verifyAndSave(GooglePlayRtdn $rtdn, string $type): bool
    {
        if (! $this->hasRequiredDataForVerification($rtdn)) {
            $this->logWarning('Missing required data for verification', [
                'rtdn_id' => $rtdn->id,
                'type' => $type,
            ]);

            return false;
        }

        $result = $this->verifyPurchaseToken(
            $rtdn->purchase_token,
            $rtdn->plan->googlePlayPlan->product_id,
            $type
        );

        $this->logDebug('Verification result', [
            'rtdn_id' => $rtdn->id,
            'result' => $result,
        ]);

        if (! $result['verified']) {
            $this->logWarning('Verification failed', [
                'rtdn_id' => $rtdn->id,
                'error' => $result['error'],
            ]);

            return false;
        }

        $rtdn->markAsVerified($result['data']);

        if ($type === self::TYPE_SUBSCRIPTION) {
            $this->saveSubscriptionData($rtdn, $result['data']);
        } else {
            $this->savePurchaseData($rtdn, $result['data']);
        }

        return true;
    }

    /**
     * Check if RTDN has required data for verification.
     */
    private function hasRequiredDataForVerification(GooglePlayRtdn $rtdn): bool
    {
        return $rtdn->purchase_token
            && $rtdn->plan
            && $rtdn->plan->googlePlayPlan
            && $rtdn->plan->googlePlayPlan->product_id;
    }

    /**
     * Check if RTDN has required data for saving.
     */
    private function canSaveData(GooglePlayRtdn $rtdn): bool
    {
        if (! $rtdn->user_id || ! $rtdn->plan_id) {
            $this->logWarning('Cannot save data without user_id or plan_id', [
                'rtdn_id' => $rtdn->id,
                'has_user_id' => ! empty($rtdn->user_id),
                'has_plan_id' => ! empty($rtdn->plan_id),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Identify user by email.
     */
    private function identifyUserByEmail(array $googleData): array
    {
        if (! isset($googleData['emailAddress'])) {
            return ['user_id' => null, 'method' => null];
        }

        $user = User::where('email', $googleData['emailAddress'])->first();

        if ($user) {
            $this->logDebug('User identified by email', [
                'email' => $googleData['emailAddress'],
                'user_id' => $user->id,
            ]);
        }

        return [
            'user_id' => $user?->id,
            'method' => $user ? 'email' : null,
        ];
    }

    /**
     * Identify user by obfuscated account ID.
     */
    private function identifyUserByObfuscatedId(array $googleData): array
    {
        if (! isset($googleData['obfuscatedExternalAccountId'])) {
            return ['user_id' => null, 'method' => null];
        }

        $existingRtdn = GooglePlayRtdn::where('google_api_response->obfuscatedExternalAccountId', $googleData['obfuscatedExternalAccountId'])
            ->whereNotNull('user_id')
            ->first();

        if ($existingRtdn) {
            $this->logDebug('User identified by obfuscated account ID', [
                'obfuscated_id' => $googleData['obfuscatedExternalAccountId'],
                'user_id' => $existingRtdn->user_id,
            ]);
        }

        return [
            'user_id' => $existingRtdn?->user_id,
            'method' => $existingRtdn ? 'obfuscated_account_id' : null,
        ];
    }

    /**
     * Create or update subscription record.
     */
    private function upsertSubscription(GooglePlayRtdn $rtdn, array $apiData): GooglePlaySubscription
    {
        $subscriptionData = $this->buildSubscriptionDataWithOfferDetails($rtdn, $apiData);

        $this->logDebug('Upserting subscription', [
            'purchase_token' => $rtdn->purchase_token,
            'status' => $subscriptionData['status'],
        ]);

        User::where('id', $rtdn->user_id)->update([
            'plan_id' => $rtdn->plan_id,
        ]);

        return GooglePlaySubscription::updateOrCreate(
            ['purchase_token' => $rtdn->purchase_token],
            $subscriptionData
        );
    }

    /**
     * Build subscription data with offer details integration.
     * This wraps the subscription service's buildSubscriptionData and enhances it with offer details.
     */
    private function buildSubscriptionDataWithOfferDetails(GooglePlayRtdn $rtdn, array $apiData): array
    {
        // Get base subscription data from subscription service
        $subscriptionData = $this->subscriptionService->buildSubscriptionData($rtdn, $apiData);

        // Check if we need to fetch offer details
        if (isset($subscriptionData['free_trial_period']['needs_offer_details'])
            && $subscriptionData['free_trial_period']['needs_offer_details'] === true) {

            $trialPeriod = $subscriptionData['free_trial_period'];
            $productId = $trialPeriod['product_id'] ?? null;
            $basePlanId = $trialPeriod['base_plan_id'] ?? null;
            $offerId = $trialPeriod['offer_id'] ?? null;

            if ($productId && $basePlanId && $offerId) {
                $this->logInfo('Fetching offer details for trial (payment state = 2)', [
                    'product_id' => $productId,
                    'base_plan_id' => $basePlanId,
                    'offer_id' => $offerId,
                ]);

                // Fetch offer details from Google Play API
                $offerResult = $this->offerService->getOfferDetails(
                    $productId,
                    $basePlanId,
                    $offerId
                );

                if ($offerResult['success'] && isset($offerResult['data'])) {
                    $trialInfoFromOffer = $this->offerService->extractTrialInfoFromOffer($offerResult['data']);

                    // Enhance trial period data with offer details
                    $subscriptionData['free_trial_period'] = $this->subscriptionService->enhanceTrialPeriodWithOfferDetails(
                        $trialPeriod,
                        $trialInfoFromOffer
                    );

                    // Recalculate trial end date if we got period info
                    if (! empty($subscriptionData['free_trial_period']['period']) && $subscriptionData['trial_start_at']) {
                        $trialEndAt = $this->calculateTrialEndDate(
                            $subscriptionData['trial_start_at'],
                            $subscriptionData['free_trial_period']
                        );
                        if ($trialEndAt) {
                            $subscriptionData['trial_end_at'] = $trialEndAt;
                        }
                    }

                    $this->logInfo('Trial offer details fetched from API', [
                        'product_id' => $productId,
                        'offer_id' => $offerId,
                        'trial_info' => $trialInfoFromOffer,
                    ]);
                } else {
                    $this->logWarning('Failed to fetch offer details for trial', [
                        'product_id' => $productId,
                        'offer_id' => $offerId,
                        'error' => $offerResult['error'] ?? 'Unknown error',
                    ]);
                }
            }
        }

        return $subscriptionData;
    }

    /**
     * Calculate trial end date based on ISO 8601 period format.
     */
    private function calculateTrialEndDate(Carbon $startDate, array $trialPeriod): ?Carbon
    {
        if (! isset($trialPeriod['period'])) {
            return null;
        }

        $period = $trialPeriod['period'];
        $cycles = $trialPeriod['cycles'] ?? 1;

        try {
            // Parse ISO 8601 period format (e.g., "P1W", "P1M", "P3M", "P1Y")
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
     * Validate offer eligibility and constraints from Google's API response.
     */
    public function validateOfferEligibility(array $purchaseData, $user, $googlePlayPlan): array
    {
        // Extract offer information from Google's API response
        $offerInfo = $this->subscriptionService->extractCompleteOfferInformation($purchaseData);

        $this->logDebug('Offer information extracted from Google Play API', [
            'offer_id' => $offerInfo['offer_id'],
            'base_plan_id' => $offerInfo['base_plan_id'],
            'promotion_code' => $offerInfo['promotion_code'],
            'offer_tags' => $offerInfo['offer_tags'],
            'is_trial_offer' => $offerInfo['is_trial_offer'] ?? false,
        ]);

        // If no offer information is present, validation passes
        if (! $offerInfo['offer_id'] && ! $offerInfo['promotion_code'] && empty($offerInfo['offer_tags'])) {
            return [
                'valid' => true,
                'offer_info' => $offerInfo,
            ];
        }

        // Check if user has already used a free trial for this product
        if ($this->isTrialOfferFromInfo($offerInfo, $googlePlayPlan)) {
            $this->logInfo('Trial offer detected', [
                'offer_id' => $offerInfo['offer_id'],
                'promotion_code' => $offerInfo['promotion_code'],
                'offer_tags' => $offerInfo['offer_tags'],
                'product_id' => $googlePlayPlan->product_id,
                'user_id' => $user->id,
            ]);

            if ($this->hasUserUsedTrialForProduct($user, $googlePlayPlan->product_id)) {
                $this->logWarning('Trial offer rejected - user already used trial', [
                    'offer_id' => $offerInfo['offer_id'],
                    'product_id' => $googlePlayPlan->product_id,
                    'user_id' => $user->id,
                ]);

                return [
                    'valid' => false,
                    'error' => 'User has already used their free trial for this product',
                    'error_code' => 'TRIAL_ALREADY_USED',
                    'offer_info' => $offerInfo,
                ];
            }

            // Mark that this is a trial offer for processing
            $offerInfo['is_trial_offer'] = true;

            $this->logInfo('Trial offer validated successfully', [
                'offer_id' => $offerInfo['offer_id'],
                'product_id' => $googlePlayPlan->product_id,
                'user_id' => $user->id,
            ]);
        }

        // Validate offer configuration if offer ID is present
        if ($offerInfo['offer_id']) {
            $offerValidation = $this->validateOfferConfiguration($offerInfo['offer_id'], $googlePlayPlan);
            if (! $offerValidation['valid']) {
                return array_merge($offerValidation, ['offer_info' => $offerInfo]);
            }
        }

        return [
            'valid' => true,
            'offer_info' => $offerInfo,
        ];
    }

    /**
     * Check if the offer is a trial offer based on extracted information.
     */
    private function isTrialOfferFromInfo(array $offerInfo, $googlePlayPlan = null): bool
    {
        // If already marked as trial from extraction, return true
        if (! empty($offerInfo['is_trial_offer'])) {
            return true;
        }

        $offerId = $offerInfo['offer_id'];
        $offerTags = $offerInfo['offer_tags'] ?? [];

        // Method 1: Check against local offers database if available
        if ($offerId && $googlePlayPlan && ! empty($googlePlayPlan->offers)) {
            return $googlePlayPlan->isTrialOffer($offerId);
        }

        // Method 2: Check offer tags for trial indicators
        if (! empty($offerTags)) {
            $trialIndicators = ['trial', 'free_trial', 'intro', 'introductory'];
            foreach ($offerTags as $tag) {
                if (in_array(strtolower($tag), $trialIndicators)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has already used a trial for this product.
     */
    private function hasUserUsedTrialForProduct($user, string $productId): bool
    {
        // Check for existing subscriptions with trial data
        $hasTrialSubscription = $user->googlePlaySubscriptions()
            ->where('product_id', $productId)
            ->whereNotNull('trial_end_at')
            ->exists();

        if ($hasTrialSubscription) {
            return true;
        }

        // Also check for subscriptions with introductory pricing that was free
        return $user->googlePlaySubscriptions()
            ->where('product_id', $productId)
            ->whereNotNull('intro_price_info')
            ->where(function ($query) {
                $query->whereRaw("JSON_EXTRACT(intro_price_info, '$.introductoryPriceAmountMicros') = '0'")
                    ->orWhereRaw("JSON_EXTRACT(intro_price_info, '$.amount_micros') = 0");
            })
            ->exists();
    }

    /**
     * Validate offer configuration against the plan.
     */
    private function validateOfferConfiguration(?string $offerId, $googlePlayPlan): array
    {
        if (! $offerId) {
            return ['valid' => true]; // No specific offer to validate
        }

        // Basic validation: ensure offer ID is reasonable
        if (strlen($offerId) < 3 || strlen($offerId) > 100) {
            return [
                'valid' => false,
                'error' => 'Invalid offer ID format',
                'error_code' => 'INVALID_OFFER_FORMAT',
            ];
        }

        // Check against local offers database if available
        if (! empty($googlePlayPlan->offers)) {
            // Check if the offer exists and is active
            if (! $googlePlayPlan->hasActiveOffer($offerId)) {
                return [
                    'valid' => false,
                    'error' => 'Offer not found or not active for this plan',
                    'error_code' => 'OFFER_NOT_ACTIVE',
                ];
            }
        } else {
            // No local offers data available, log a warning
            $this->logOfferValidationWarning($offerId, $googlePlayPlan->product_id);
        }

        return ['valid' => true];
    }

    /**
     * Log warning when offer validation is limited due to missing local data.
     */
    private function logOfferValidationWarning(string $offerId, string $productId): void
    {
        $this->logWarning('Limited offer validation - no local offers data available', [
            'offer_id' => $offerId,
            'product_id' => $productId,
            'recommendation' => 'Run google-play:fetch-offers command to improve validation',
        ]);
    }

    /**
     * Handle linked purchase token for plan changes.
     */
    private function handleLinkedPurchaseToken(?string $linkedPurchaseToken): void
    {
        if (! $linkedPurchaseToken) {
            return;
        }

        GooglePlaySubscription::where('purchase_token', $linkedPurchaseToken)
            ->update(['status' => GooglePlaySubscription::STATUS_EXPIRED]);

        $this->logInfo('Linked subscription marked as expired', [
            'linked_purchase_token' => $linkedPurchaseToken,
        ]);
    }

    /**
     * Handle subscription renewal.
     */
    private function handleSubscriptionRenewal(GooglePlayRtdn $rtdn, GooglePlaySubscription $subscription): void
    {
        $isRenewal = $rtdn->notification_type === 'subscription.renewed';
        $isRecovered = $rtdn->notification_type === 'subscription.recovered';
        $isRestarted = $rtdn->notification_type === 'subscription.restarted';

        if (($isRenewal || $isRecovered || $isRestarted) && $subscription->status === GooglePlaySubscription::STATUS_ACTIVE) {
            $updateData = [];

            if ($isRenewal) {
                $updateData['resumed_at'] = now();
            } elseif ($isRecovered) {
                $updateData['resumed_at'] = now();
                // Clear any previous cancellation data for recovered subscriptions
                $updateData['canceled_at'] = null;
            } elseif ($isRestarted) {
                // User restored subscription from Play Store
                $updateData['resumed_at'] = now();
                $updateData['canceled_at'] = null;
            }

            if (! empty($updateData)) {
                $subscription->update($updateData);

                $this->logInfo('Subscription lifecycle event processed', [
                    'subscription_id' => $subscription->id,
                    'event_type' => $rtdn->notification_type,
                    'updated_fields' => array_keys($updateData),
                ]);
            }
        }
    }

    /**
     * Update subscription cancellation.
     */
    private function updateSubscriptionCancellation(GooglePlaySubscription $subscription, array $apiData): void
    {
        if (! isset($apiData['userCancellationTimeMillis'])) {
            return;
        }

        $canceledAt = Carbon::createFromTimestampMs($apiData['userCancellationTimeMillis']);
        $subscription->update(['canceled_at' => $canceledAt]);

        $this->logInfo('Subscription cancellation recorded', [
            'subscription_id' => $subscription->id,
            'canceled_at' => $canceledAt->toDateTimeString(),
        ]);
    }

    /**
     * Create or update subscription item with proper lifecycle handling.
     */
    private function upsertSubscriptionItem(GooglePlaySubscription $subscription, GooglePlayRtdn $rtdn, array $apiData): void
    {
        // Get price from API data, fallback to GooglePlayPlan if not available
        $priceAmountMicros = $apiData['priceAmountMicros'] ?? $rtdn->plan->googlePlayPlan->price_micros ?? 0;
        $priceCurrencyCode = $apiData['priceCurrencyCode'] ?? $rtdn->plan->googlePlayPlan->currency_code ?? 'USD';

        $itemData = [
            'product_id' => $rtdn->plan->googlePlayPlan->product_id,
            'quantity' => 1,
            'price_amount_micros' => $priceAmountMicros,
            'price_currency_code' => $priceCurrencyCode,
            'billing_period' => $rtdn->plan->googlePlayPlan->billing_period ?? null,
        ];

        // For renewals, updates, and lifecycle events, ensure we update the subscription item
        $subscriptionItem = GooglePlaySubscriptionItem::updateOrCreate(
            [
                'google_play_subscription_id' => $subscription->id,
                'plan_id' => $rtdn->plan_id,
            ],
            $itemData
        );

        // Log additional context for lifecycle events
        $logContext = [
            'subscription_id' => $subscription->id,
            'subscription_item_id' => $subscriptionItem->id,
            'plan_id' => $rtdn->plan_id,
            'notification_type' => $rtdn->notification_type,
            'was_recently_created' => $subscriptionItem->wasRecentlyCreated,
        ];

        if (! $subscriptionItem->wasRecentlyCreated) {
            $logContext['action'] = 'updated_existing_item';
        }

        $this->logDebug('Subscription item processed for lifecycle event', $logContext);
    }

    /**
     * Create or update purchase record.
     */
    private function upsertPurchase(GooglePlayRtdn $rtdn, array $apiData): GooglePlayPurchase
    {
        $purchaseData = $this->buildPurchaseData($rtdn, $apiData);

        $this->logDebug('Upserting purchase', [
            'purchase_token' => $rtdn->purchase_token,
            'status' => $purchaseData['status'],
        ]);

        return GooglePlayPurchase::updateOrCreate(
            ['purchase_token' => $rtdn->purchase_token],
            $purchaseData
        );
    }

    /**
     * Build purchase data array.
     */
    private function buildPurchaseData(GooglePlayRtdn $rtdn, array $apiData): array
    {
        // Get price from API data, fallback to GooglePlayPlan if not available
        $priceAmountMicros = $apiData['priceAmountMicros'] ?? $rtdn->plan->googlePlayPlan->price_micros ?? 0;
        $priceCurrencyCode = $apiData['priceCurrencyCode'] ?? $rtdn->plan->googlePlayPlan->currency_code ?? 'USD';

        return [
            'user_id' => $rtdn->user_id,
            'order_id' => $apiData['orderId'] ?? null,
            'product_id' => $rtdn->plan->googlePlayPlan->product_id,
            'plan_id' => $rtdn->plan_id,
            'quantity' => $apiData['quantity'] ?? 1,
            'status' => $this->mapPurchaseStatus($apiData),
            'purchased_at' => $this->parseTimestamp($apiData['purchaseTimeMillis'] ?? null),
            'price_amount_micros' => $priceAmountMicros,
            'price_currency_code' => $priceCurrencyCode,
            'country_code' => $apiData['regionCode'] ?? null,
            'purchase_state' => $apiData['purchaseState'] ?? GooglePlayPurchase::PURCHASE_STATE_PURCHASED,
            'consumption_state' => $apiData['consumptionState'] ?? null,
            'metadata' => $this->buildMetadata($apiData),
        ];
    }

    /**
     * Build metadata array.
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
     */
    private function parseTimestamp(?string $timestampMs): ?Carbon
    {
        return $timestampMs ? Carbon::createFromTimestampMs($timestampMs) : null;
    }

    /**
     * Log subscription saved.
     */
    private function logSubscriptionSaved(GooglePlaySubscription $subscription, GooglePlayRtdn $rtdn): void
    {
        $this->logInfo('Google Play subscription data saved', [
            'subscription_id' => $subscription->id,
            'rtdn_id' => $rtdn->id,
            'is_renewal' => $rtdn->notification_type === 'subscription.renewed',
            'status' => $subscription->status,
            'expires_at' => $subscription->expires_at?->toDateTimeString(),
            'linked_purchase_token' => $subscription->linked_purchase_token,
        ]);
    }
}
