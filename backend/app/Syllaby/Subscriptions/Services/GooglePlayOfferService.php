<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;

class GooglePlayOfferService extends GooglePlayBaseService
{
    /**
     * Get offer details from Google Play API.
     *
     * @param  string  $productId  The subscription product ID
     * @param  string  $basePlanId  The base plan ID
     * @param  string  $offerId  The offer ID
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getOfferDetails(string $productId, string $basePlanId, string $offerId): array
    {
        try {
            $endpoint = "/subscriptions/{$productId}/basePlans/{$basePlanId}/offers/{$offerId}";

            $this->logInfo('Fetching offer details from Google Play API', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
                'offer_id' => $offerId,
                'endpoint' => $endpoint,
            ]);

            $result = $this->makeApiRequest('get', $endpoint, [], [
                'context' => 'get_offer_details',
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
                'offer_id' => $offerId,
            ]);

            if ($result['success'] && isset($result['data'])) {
                $this->logDebug('Offer details retrieved successfully', [
                    'product_id' => $productId,
                    'base_plan_id' => $basePlanId,
                    'offer_id' => $offerId,
                    'offer_data' => $result['data'],
                ]);

                return [
                    'success' => true,
                    'data' => $result['data'],
                    'error' => null,
                ];
            }

            $this->logWarning('Failed to retrieve offer details', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
                'offer_id' => $offerId,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $result['error'] ?? 'Failed to retrieve offer details',
            ];
        } catch (Exception $e) {
            $this->logError('Exception while fetching offer details', [
                'product_id' => $productId,
                'base_plan_id' => $basePlanId,
                'offer_id' => $offerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract trial information from offer details.
     *
     * @param  array  $offerData  The offer data from Google Play API
     * @return array{is_trial: bool, trial_duration: string|null, trial_cycles: int|null}
     */
    public function extractTrialInfoFromOffer(array $offerData): array
    {
        $trialInfo = [
            'is_trial' => false,
            'trial_duration' => null,
            'trial_cycles' => null,
            'trial_phases' => [],
        ];

        $phases = $offerData['phases'] ?? [];

        foreach ($phases as $index => $phase) {
            if ($this->isTrialPhase($phase)) {
                $trialInfo['is_trial'] = true;
                $trialInfo['trial_duration'] = $phase['duration'] ?? null;
                $trialInfo['trial_cycles'] = $phase['recurrenceCount'] ?? 1;
                $trialInfo['trial_phases'][] = [
                    'phase_index' => $index,
                    'duration' => $phase['duration'] ?? null,
                    'recurrence_count' => $phase['recurrenceCount'] ?? null,
                ];
            }
        }

        $offerTags = $offerData['offerTags'] ?? [];
        foreach ($offerTags as $tag) {
            if ($this->isTrialTag($tag)) {
                $trialInfo['is_trial'] = true;
                break;
            }
        }

        $this->logDebug('Trial information extracted from offer', [
            'offer_id' => $offerData['offerId'] ?? null,
            'trial_info' => $trialInfo,
        ]);

        return $trialInfo;
    }

    /**
     * Check if a phase is a trial phase based on pricing.
     *
     * @param  array  $phase  The phase data
     */
    private function isTrialPhase(array $phase): bool
    {
        $regionalConfigs = $phase['regionalConfigs'] ?? [];

        foreach ($regionalConfigs as $config) {
            if (isset($config['free'])) {
                return true;
            }

            if (isset($config['absolutePrice'])) {
                $price = $config['absolutePrice'];
                $units = (int) ($price['units'] ?? 0);
                $nanos = (int) ($price['nanos'] ?? 0);

                if ($units === 0 && $nanos === 0) {
                    return true;
                }
            }
        }

        $otherRegionsConfig = $phase['otherRegionsConfig'] ?? [];
        return isset($otherRegionsConfig['free']);
    }

    /**
     * Check if an offer tag indicates a trial.
     *
     * @param  string  $tag  The offer tag
     */
    private function isTrialTag(string $tag): bool
    {
        $trialTags = ['trial', 'free_trial', 'intro', 'introductory', 'free'];
        return in_array(strtolower($tag), $trialTags);
    }

    /**
     * Get offer details by purchase data.
     * Extracts necessary IDs from purchase data and fetches offer details.
     *
     * @param  array  $purchaseData  The purchase data from verification
     * @param  string|null  $productId  Override product ID if known
     * @return array{success: bool, data: array|null, error: string|null, trial_info: array|null}
     */
    public function getOfferDetailsByPurchaseData(array $purchaseData, ?string $productId = null): array
    {
        $offerInfo = $this->extractOfferInfoFromPurchaseData($purchaseData);

        if (empty($offerInfo['offer_id']) || empty($offerInfo['base_plan_id'])) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Missing offer ID or base plan ID in purchase data',
                'trial_info' => null,
            ];
        }

        $productId = $productId ?: $offerInfo['product_id'] ?? null;

        if (empty($productId)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Missing product ID',
                'trial_info' => null,
            ];
        }

        $result = $this->getOfferDetails($productId, $offerInfo['base_plan_id'], $offerInfo['offer_id']);

        if ($result['success'] && $result['data']) {
            $trialInfo = $this->extractTrialInfoFromOffer($result['data']);
            return array_merge($result, ['trial_info' => $trialInfo]);
        }

        return array_merge($result, ['trial_info' => null]);
    }

    /**
     * Extract offer information from purchase data.
     */
    private function extractOfferInfoFromPurchaseData(array $purchaseData): array
    {
        $offerInfo = [
            'offer_id' => null,
            'base_plan_id' => null,
            'product_id' => null,
        ];

        if (isset($purchaseData['lineItems'][0])) {
            $lineItem = $purchaseData['lineItems'][0];

            if (isset($lineItem['offerDetails'])) {
                $offerInfo['offer_id'] = $lineItem['offerDetails']['offerId'] ?? null;
                $offerInfo['base_plan_id'] = $lineItem['offerDetails']['basePlanId'] ?? null;
            }

            $offerInfo['product_id'] = $lineItem['productId'] ?? null;
        }

        return $offerInfo;
    }
}
