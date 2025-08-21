<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Subscriptions\Events\GooglePlayProductPurchased;
use App\Syllaby\Subscriptions\Events\GooglePlaySubscriptionPurchased;
use App\Syllaby\Subscriptions\Services\GooglePlayVerificationService;

class GooglePlayCheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(private GooglePlayVerificationService $verificationService)
    {
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->user()->usesGooglePlay()) {
            return $this->respondWithError(
                'Google Play purchases are not supported for this user',
                Response::HTTP_FORBIDDEN,
                'GOOGLE_PLAY_NOT_SUPPORTED'
            );
        }

        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'purchase_token' => 'required|string',
        ]);

        try {
            $requestHash = $this->generateRequestHash($validated, $this->user()->id);
            if ($this->isRecentDuplicateRequest($requestHash)) {
                Log::info('Duplicate Google Play checkout request blocked', [
                    'user_id' => $this->user()->id,
                    'request_hash' => $requestHash,
                ]);

                return $this->respondWithError(
                    'Duplicate request detected. Please wait before retrying.',
                    429,
                    'DUPLICATE_REQUEST'
                );
            }

            $googlePlayPlan = GooglePlayPlan::where('plan_id', $validated['plan_id'])->first();
            if (! $googlePlayPlan || blank($googlePlayPlan->metadata)) {
                return $this->errorNotFound('Google Play plan not found for this plan ID', 'PLAN_NOT_MAPPED');
            }

            $existingRtdn = $this->findExistingRtdn($validated['purchase_token'], $this->user()->id);
            if ($existingRtdn) {
                return $this->existingRtdnResponse($existingRtdn);
            }

            $rtdn = $this->createPendingRtdn($request, $validated, $googlePlayPlan);
            $this->logPendingRtdn($rtdn, $request, $validated);

            $verificationResult = $this->verificationService->verifyPurchaseToken(
                $validated['purchase_token'],
                $googlePlayPlan->product_id,
                $googlePlayPlan->product_type === 'inapp' ? 'product' : 'subscription'
            );

            if (! $verificationResult['verified']) {
                $this->handleVerificationFailure($rtdn, $verificationResult, $request, $validated);

                return $this->errorWrongArgs('Purchase verification failed: '.($verificationResult['error'] ?? 'Unknown error'));
            }

            $purchaseData = $verificationResult['data'];
            $purchaseState = $purchaseData['purchaseState'] ?? null;
            $acknowledgementState = $purchaseData['acknowledgementState'] ?? 0;

            $status = $googlePlayPlan->product_type === 'inapp'
                ? $this->verificationService->mapPurchaseStatus($purchaseData)
                : $this->verificationService->mapSubscriptionStatus($purchaseData);

            $offerValidation = $this->verificationService->validateOfferEligibility(
                $purchaseData,
                $this->user(),
                $googlePlayPlan
            );

            if (! $offerValidation['valid']) {
                $this->handleVerificationFailure($rtdn, $offerValidation, $request, $validated);

                return $this->respondWithError(
                    $offerValidation['error'] ?? 'Offer validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $offerValidation['error_code'] ?? 'OFFER_VALIDATION_FAILED'
                );
            }

            $offerInfo = $offerValidation['offer_info'] ?? [];

            $this->processSuccessfulVerification($rtdn, $purchaseData, $request, $offerInfo);
            $this->logSuccessfulVerification($rtdn, $request, $validated, $status, $offerInfo);

            return $this->successResponse($rtdn, $status, $purchaseState, $acknowledgementState, $purchaseData, $offerInfo);
        } catch (\Exception $e) {
            Log::error('Failed to verify Google Play purchase', [
                'user_id' => $this->user()->id,
                'plan_id' => $validated['plan_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorInternalError('Failed to verify purchase', 'SERVER_ERROR');
        }
    }

    private function findExistingRtdn(string $purchaseToken, int $userId): ?GooglePlayRtdn
    {
        return GooglePlayRtdn::where('purchase_token', $purchaseToken)
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    private function existingRtdnResponse(GooglePlayRtdn $existingRtdn): JsonResponse
    {
        return $this->respondWithArray([
            'rtdn_id' => $existingRtdn->id,
            'status' => $existingRtdn->getStatusLabel(),
            'notification_type' => $existingRtdn->notification_type,
            'google_verified' => $existingRtdn->google_api_verified,
            'processed_at' => $existingRtdn->processed_at,
            'created_at' => $existingRtdn->created_at,
        ], 200, 'Purchase already processed');
    }

    private function createPendingRtdn(Request $request, array $validated, GooglePlayPlan $googlePlayPlan): GooglePlayRtdn
    {
        try {
            $notificationType = $googlePlayPlan->product_type === 'inapp'
                ? 'product.purchased'
                : 'subscription.purchased';

            return GooglePlayRtdn::create([
                'user_id' => $this->user()->id,
                'plan_id' => $validated['plan_id'],
                'purchase_token' => $validated['purchase_token'],
                'notification_type' => $notificationType,
                'status' => GooglePlayRtdn::STATUS_PENDING,
                'google_api_verified' => false,
                'rtdn_response' => [
                    'source' => 'checkout_api',
                    'product_id' => $googlePlayPlan->product_id,
                    'product_type' => $googlePlayPlan->product_type,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                $existing = $this->findExistingRtdn($validated['purchase_token'], $this->user()->id);
                if ($existing) {
                    Log::info('Race condition detected, returning existing RTDN', [
                        'rtdn_id' => $existing->id,
                        'user_id' => $this->user()->id,
                    ]);

                    return $existing;
                }
            }
            throw $e;
        }
    }

    private function logPendingRtdn(GooglePlayRtdn $rtdn, Request $request, array $validated): void
    {
        Log::info('Created pending RTDN record for purchase verification', [
            'rtdn_id' => $rtdn->id,
            'user_id' => $this->user()->id,
            'plan_id' => $validated['plan_id'],
            'purchase_token' => $validated['purchase_token'],
        ]);
    }

    private function handleVerificationFailure(GooglePlayRtdn $rtdn, array $verificationResult, Request $request, array $validated): void
    {
        $rtdn->update([
            'status' => GooglePlayRtdn::STATUS_FAILED,
            'processing_errors' => [
                'error' => $verificationResult['error'] ?? 'Verification failed',
                'timestamp' => now()->toIso8601String(),
            ],
            'processed_at' => now(),
        ]);

        Log::warning('Google Play purchase verification failed', [
            'rtdn_id' => $rtdn->id,
            'user_id' => $this->user()->id,
            'plan_id' => $validated['plan_id'],
            'error' => $verificationResult['error'] ?? null,
        ]);
    }

    private function processSuccessfulVerification(GooglePlayRtdn $rtdn, array $purchaseData, Request $request, array $offerInfo = []): void
    {
        try {
            DB::transaction(function () use ($rtdn, $purchaseData, $offerInfo) {
                $updatedResponse = array_merge($rtdn->rtdn_response ?? [], [
                    'offer_id' => $offerInfo['offer_id'] ?? null,
                    'promotion_code' => $offerInfo['promotion_code'] ?? null,
                    'promotion_type' => $offerInfo['promotion_type'] ?? null,
                    'base_plan_id' => $offerInfo['base_plan_id'] ?? null,
                    'offer_tags' => $offerInfo['offer_tags'] ?? null,
                    'is_trial_offer' => $offerInfo['is_trial_offer'] ?? false,
                ]);

                $rtdn->update([
                    'google_api_verified' => true,
                    'google_api_response' => $purchaseData,
                    'rtdn_response' => $updatedResponse,
                    'status' => GooglePlayRtdn::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);

                if ($rtdn->isSubscriptionNotification()) {
                    $this->verificationService->saveSubscriptionFromExistingData($rtdn);
                    event(new GooglePlaySubscriptionPurchased($rtdn, $this->user()));
                } elseif ($rtdn->isOneTimeProductNotification()) {
                    $this->verificationService->savePurchaseFromExistingData($rtdn);
                    event(new GooglePlayProductPurchased($rtdn, $this->user()));
                }
            });
        } catch (\Throwable $e) {
            Log::error('DB::transaction failed in processSuccessfulVerification', [
                'rtdn_id' => $rtdn->id,
                'user_id' => $this->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function logSuccessfulVerification(GooglePlayRtdn $rtdn, Request $request, array $validated, string $status, array $offerInfo = []): void
    {
        $eventDispatched = $rtdn->isSubscriptionNotification()
            ? 'GooglePlaySubscriptionPurchased'
            : ($rtdn->isOneTimeProductNotification() ? 'GooglePlayProductPurchased' : 'None');

        Log::info('Google Play purchase verified successfully', [
            'rtdn_id' => $rtdn->id,
            'user_id' => $this->user()->id,
            'plan_id' => $validated['plan_id'],
            'purchase_token' => $validated['purchase_token'],
            'offer_id' => $offerInfo['offer_id'] ?? null,
            'promotion_code' => $offerInfo['promotion_code'] ?? null,
            'base_plan_id' => $offerInfo['base_plan_id'] ?? null,
            'status' => $status,
            'notification_type' => $rtdn->notification_type,
            'event_dispatched' => $eventDispatched,
        ]);
    }

    private function successResponse(GooglePlayRtdn $rtdn, string $status, $purchaseState, $acknowledgementState, array $purchaseData, array $offerInfo = []): JsonResponse
    {
        $response = [
            'rtdn_id' => $rtdn->id,
            'status' => $status,
            'purchase_state' => $purchaseState,
            'acknowledged' => $acknowledgementState === 1,
            'order_id' => $purchaseData['orderId'] ?? null,
            'purchase_time' => isset($purchaseData['purchaseTimeMillis'])
                ? (int) ($purchaseData['purchaseTimeMillis'] / 1000)
                : null,
            'waiting_for_rtdn' => true,
            'offer_id' => $offerInfo['offer_id'] ?? null,
            'promotion_code' => $offerInfo['promotion_code'] ?? null,
            'base_plan_id' => $offerInfo['base_plan_id'] ?? null,
        ];

        if ($rtdn->isSubscriptionNotification()) {
            $response['expiry_time'] = isset($purchaseData['expiryTimeMillis'])
                ? (int) ($purchaseData['expiryTimeMillis'] / 1000)
                : null;
            $response['auto_renewing'] = $purchaseData['autoRenewing'] ?? false;

            if (! empty($offerInfo['is_trial_offer'])) {
                $response['is_trial'] = true;
            }
        }

        return $this->respondWithArray($response, 200, 'Purchase verified successfully');
    }

    private function generateRequestHash(array $validated, int $userId): string
    {
        return hash('sha256', json_encode([
            'user_id' => $userId,
            'plan_id' => $validated['plan_id'],
            'purchase_token' => $validated['purchase_token'],
        ]));
    }

    private function isRecentDuplicateRequest(string $requestHash): bool
    {
        $key = "gp_checkout_request:{$requestHash}";
        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, 300);

        return false;
    }

    private function isUniqueConstraintViolation(\Illuminate\Database\QueryException $e): bool
    {
        return in_array($e->getCode(), ['23000', '23505']) ||
            str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
}
