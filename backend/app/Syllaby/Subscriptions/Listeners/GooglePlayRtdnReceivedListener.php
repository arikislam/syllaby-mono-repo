<?php

namespace App\Syllaby\Subscriptions\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use App\Syllaby\Subscriptions\GooglePlaySubscription;
use App\Syllaby\Subscriptions\Events\GooglePlayRtdnHandled;
use App\Syllaby\Subscriptions\Events\GooglePlayRtdnReceived;
use App\Syllaby\Subscriptions\Services\GooglePlayRtdnMapper;
use App\Syllaby\Subscriptions\Services\GooglePlayVerificationService;
use App\Syllaby\Subscriptions\Enums\GooglePlaySubscriptionNotification;

class GooglePlayRtdnReceivedListener
{
    /**
     * RTDN fields for creating new records.
     */
    private const RTDN_BASE_FIELDS = [
        'status' => GooglePlayRtdn::STATUS_PENDING,
    ];

    public function __construct(
        private GooglePlayVerificationService $verificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GooglePlayRtdnReceived $event): void
    {
        $payload = $event->payload;

        // Handle test notifications
        if ($this->isTestNotification($payload)) {
            return;
        }

        // Extract and validate notification data
        $notificationData = $this->extractNotificationData($payload);
        if (! $notificationData) {
            return;
        }

        // Determine notification handling strategy
        $subscriptionEnum = GooglePlaySubscriptionNotification::fromString($notificationData['type']);
        $isRenewalOrCancellation = $subscriptionEnum &&
            ($subscriptionEnum->isRenewal() || $subscriptionEnum->isCancellation());

        if ($isRenewalOrCancellation) {
            $this->handleRenewalOrCancellation($notificationData, $payload);
        } else {
            $this->handleInitialPurchase($notificationData, $payload);
        }
    }

    /**
     * Check if this is a test notification.
     */
    private function isTestNotification(array $payload): bool
    {
        if (isset($payload['testNotification'])) {
            Log::info('Google Play test notification received', $payload);

            return true;
        }

        return false;
    }

    /**
     * Extract and validate notification data.
     */
    private function extractNotificationData(array $payload): ?array
    {
        $purchaseToken = $this->extractPurchaseToken($payload);
        $notificationType = GooglePlayRtdnMapper::determineNotificationType($payload);

        if (! $purchaseToken || ! $notificationType) {
            Log::warning('Invalid Google Play RTDN payload - missing purchase token or notification type', $payload);

            return null;
        }

        return [
            'token' => $purchaseToken,
            'type' => $notificationType,
            'subscriptionId' => $this->extractSubscriptionId($payload),
        ];
    }

    /**
     * Handle renewal or cancellation notifications.
     */
    private function handleRenewalOrCancellation(array $notificationData, array $payload): void
    {
        $purchaseToken = $notificationData['token'];

        // Check for duplicate notification first
        if ($this->isDuplicateNotification($purchaseToken, $notificationData['type'], $payload, 'renewal/cancellation')) {
            return;
        }

        // Try to find user from previous RTDN records
        $previousRtdn = GooglePlayRtdn::where('purchase_token', $purchaseToken)
            ->whereNotNull('user_id')
            ->latest()
            ->first();

        if (! $previousRtdn) {
            // Try to find user from Google Play Subscription
            $subscription = GooglePlaySubscription::where('purchase_token', $purchaseToken)->first();
            if (! $subscription || ! $subscription->user_id) {
                Log::critical('Cannot find user for renewal/cancellation notification', [
                    'purchase_token' => $purchaseToken,
                    'notification_type' => $notificationData['type'],
                    'message_id' => $payload['messageId'] ?? null,
                    'action' => 'webhook_rejected',
                    'reason' => 'No user association found in RTDN or subscription records',
                ]);

                // Do not create orphaned RTDN - reject processing
                return;
            }
            $userId = $subscription->user_id;
            $planId = $subscription->plan_id;
        } else {
            $userId = $previousRtdn->user_id;
            $planId = $previousRtdn->plan_id;
        }

        // Always create new RTDN record for renewal/cancellation
        $rtdnData = [
            'user_id' => $userId,
            'purchase_token' => $purchaseToken,
            'plan_id' => $planId,
            'message_id' => $payload['messageId'] ?? null,
            'notification_type' => $notificationData['type'],
            'rtdn_response' => $payload,
            'google_api_verified' => false, // Will be verified in the handler
        ];

        $this->createRtdnRecord($rtdnData, [
            'source' => 'renewal_or_cancellation',
            'notification_type' => $notificationData['type'],
        ]);
    }

    /**
     * Handle initial purchase notifications.
     */
    private function handleInitialPurchase(array $notificationData, array $payload): void
    {
        $purchaseToken = $notificationData['token'];

        // Check for duplicate notification first
        if ($this->isDuplicateNotification($purchaseToken, $notificationData['type'], $payload, 'initial purchase')) {
            return;
        }

        // First check if we have an existing RTDN record from checkout
        $existingRtdn = GooglePlayRtdn::where('purchase_token', $purchaseToken)
            ->whereNotNull('user_id')
            ->latest()
            ->first();

        if ($existingRtdn) {
            // Update existing RTDN with webhook data and dispatch event
            DB::transaction(function () use ($existingRtdn, $notificationData, $payload) {
                $existingRtdn->update([
                    'notification_type' => $notificationData['type'],
                    'rtdn_response' => $payload,
                    'message_id' => $payload['messageId'] ?? null,
                ]);
            });

            // Dispatch event outside transaction
            event(new GooglePlayRtdnHandled($existingRtdn));

            return;
        }

        // No existing RTDN with user - this should not happen in normal flow
        // Log as critical issue and do not create orphaned RTDN
        Log::critical('Google Play initial purchase notification received without user association', [
            'purchase_token' => $purchaseToken,
            'notification_type' => $notificationData['type'],
            'product_id' => $notificationData['subscriptionId'] ?? null,
            'message_id' => $payload['messageId'] ?? null,
            'action' => 'webhook_rejected',
            'reason' => 'User must initiate purchase through app first',
        ]);

        // Do not create RTDN record - this forces proper app-initiated flow
        // The webhook will be acknowledged but not processed
    }

    /**
     * Handle external purchase (not initiated through checkout).
     */
    private function handleExternalPurchase(array $notificationData, array $payload): void
    {
        $purchaseToken = $notificationData['token'];
        $notificationType = $notificationData['type'];
        $subscriptionId = $notificationData['subscriptionId'];

        // Check for duplicate
        if ($this->isDuplicateNotification($purchaseToken, $notificationType, $payload, 'initial purchase')) {
            return;
        }

        // Get plan ID from subscription ID
        $planId = $this->getPlanIdFromSubscriptionId($subscriptionId);

        // Verify and identify user
        $verificationResult = $this->verifyAndIdentifyUser($notificationData);
        if (! $verificationResult) {
            return;
        }

        ['userId' => $userId, 'googleData' => $googleData] = $verificationResult;

        // Check for duplicate again before creating
        if ($this->isDuplicateNotification($purchaseToken, $notificationType, $payload, 'external purchase')) {
            return;
        }

        // Create RTDN record
        $this->createRtdnRecord([
            'user_id' => $userId,
            'plan_id' => $planId,
            'purchase_token' => $purchaseToken,
            'notification_type' => $notificationType,
            'rtdn_response' => $payload,
            'message_id' => $payload['messageId'] ?? null,
            'google_api_response' => $googleData,
            'google_api_verified' => true,
        ], [
            'subscription_id' => $subscriptionId,
            'obfuscated_account_id' => $googleData['obfuscatedExternalAccountId'] ?? null,
        ]);
    }

    /**
     * Check if this is a duplicate notification based on message_id or recent timing.
     */
    private function isDuplicateNotification(
        string $purchaseToken,
        string $notificationType,
        array $payload,
        string $context = ''
    ): bool {
        $messageId = $payload['messageId'] ?? null;

        // First check for exact message_id duplicate (most reliable)
        if ($messageId) {
            $duplicate = GooglePlayRtdn::where('message_id', $messageId)->first();
            if ($duplicate) {
                Log::info("Duplicate Google Play RTDN received (same message_id) for {$context}", [
                    'purchase_token' => $purchaseToken,
                    'notification_type' => $notificationType,
                    'message_id' => $messageId,
                    'existing_rtdn_id' => $duplicate->id,
                ]);

                return true;
            }
        }

        // Check for recent duplicate (same purchase token + notification type within 2 minutes)
        $recentDuplicate = GooglePlayRtdn::where('purchase_token', $purchaseToken)
            ->where('notification_type', $notificationType)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->first();

        if ($recentDuplicate) {
            Log::info("Recent duplicate Google Play RTDN received for {$context}", [
                'purchase_token' => $purchaseToken,
                'notification_type' => $notificationType,
                'recent_rtdn_id' => $recentDuplicate->id,
                'recent_created_at' => $recentDuplicate->created_at,
            ]);

            // Update message_id if different and not set
            if ($messageId && ! $recentDuplicate->message_id) {
                $recentDuplicate->update(['message_id' => $messageId]);
            }

            return true;
        }

        return false;
    }

    /**
     * Find existing source data for renewal/cancellation.
     */
    private function findExistingSourceData(string $purchaseToken): ?array
    {
        // Try to find from existing RTDN
        $existingRtdn = GooglePlayRtdn::where('purchase_token', $purchaseToken)
            ->whereNotNull('user_id')
            ->whereNotNull('plan_id')
            ->latest()
            ->first();

        if ($existingRtdn) {
            return [
                'user_id' => $existingRtdn->user_id,
                'plan_id' => $existingRtdn->plan_id,
            ];
        }

        // Try to find from subscription
        $subscription = GooglePlaySubscription::where('purchase_token', $purchaseToken)->first();
        if ($subscription && $subscription->user_id && $subscription->plan_id) {
            return [
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
            ];
        }

        return null;
    }

    /**
     * Find unverified RTDN from checkout.
     */
    private function findUnverifiedRtdn(string $purchaseToken): ?GooglePlayRtdn
    {
        return GooglePlayRtdn::where('purchase_token', $purchaseToken)
            ->where('google_api_verified', false)
            ->first();
    }

    /**
     * Get plan ID from subscription ID.
     */
    private function getPlanIdFromSubscriptionId(?string $subscriptionId): ?int
    {
        if (! $subscriptionId) {
            return null;
        }

        $googlePlayPlan = GooglePlayPlan::where('product_id', $subscriptionId)->first();

        return $googlePlayPlan?->plan_id;
    }

    /**
     * Verify purchase and identify user.
     */
    private function verifyAndIdentifyUser(array $notificationData): ?array
    {
        $purchaseToken = $notificationData['token'];
        $notificationType = $notificationData['type'];
        $subscriptionId = $notificationData['subscriptionId'];

        if (! $subscriptionId) {
            Log::warning('Cannot verify Google Play RTDN without subscription/product ID', [
                'purchase_token' => $purchaseToken,
                'notification_type' => $notificationType,
            ]);

            return null;
        }

        try {
            $type = str_starts_with($notificationType, 'product.') ? 'product' : 'subscription';
            $result = $this->verificationService->verifyPurchaseToken(
                $purchaseToken,
                $subscriptionId,
                $type
            );

            if (! $result['verified'] || ! $result['data']) {
                throw new \Exception('Failed to verify with Google Play API');
            }

            $googleData = $result['data'];
            $userIdentification = $this->verificationService->identifyUser($googleData);
            $userId = $userIdentification['user_id'];

            if ($userId) {
                Log::info('Found user from Google Play data', [
                    'method' => $userIdentification['method'],
                    'user_id' => $userId,
                ]);
            } else {
                Log::warning('Cannot process Google Play RTDN - user not found', [
                    'purchase_token' => $purchaseToken,
                    'subscription_id' => $subscriptionId,
                    'email' => $googleData['emailAddress'] ?? 'not provided',
                    'obfuscated_account_id' => $googleData['obfuscatedExternalAccountId'] ?? 'not provided',
                ]);

                return null;
            }

            return ['userId' => $userId, 'googleData' => $googleData];

        } catch (\Exception $e) {
            Log::error('Failed to verify Google Play purchase for external RTDN', [
                'purchase_token' => $purchaseToken,
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);

            return null;
        }
    }

    /**
     * Create RTDN record and dispatch event.
     */
    private function createRtdnRecord(array $data, array $logContext = []): void
    {
        // If already verified, set status to processed
        if (isset($data['google_api_verified']) && $data['google_api_verified']) {
            $data['status'] = GooglePlayRtdn::STATUS_PROCESSED;
            $data['processed_at'] = now();
        }

        // Wrap RTDN creation and event dispatch in transaction
        $rtdn = DB::transaction(function () use ($data, $logContext) {
            $rtdn = GooglePlayRtdn::create(array_merge(self::RTDN_BASE_FIELDS, $data));

            $defaultLogContext = [
                'rtdn_id' => $rtdn->id,
                'user_id' => $rtdn->user_id,
                'plan_id' => $rtdn->plan_id,
            ];

            Log::info('Created RTDN record for Google Play purchase',
                array_merge($defaultLogContext, $logContext)
            );

            return $rtdn;
        });

        // Dispatch event outside transaction to avoid issues with event listeners
        event(new GooglePlayRtdnHandled($rtdn));
    }

    /**
     * Extract purchase token from payload.
     */
    private function extractPurchaseToken(array $payload): ?string
    {
        $paths = [
            ['subscriptionNotification', 'purchaseToken'],
            ['oneTimeProductNotification', 'purchaseToken'],
            ['voidedPurchaseNotification', 'purchaseToken'],
        ];

        foreach ($paths as $path) {
            $value = $this->getNestedValue($payload, $path);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extract subscription/product ID from payload.
     */
    private function extractSubscriptionId(array $payload): ?string
    {
        $paths = [
            ['subscriptionNotification', 'subscriptionId'],
            ['oneTimeProductNotification', 'sku'],
        ];

        foreach ($paths as $path) {
            $value = $this->getNestedValue($payload, $path);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get nested value from array using path.
     */
    private function getNestedValue(array $array, array $path)
    {
        $current = $array;
        foreach ($path as $key) {
            if (! isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }
}
