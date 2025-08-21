<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\Contracts\GooglePlayApiClientInterface;

/**
 * Base Google Play Service with shared functionality
 */
abstract class GooglePlayBaseService
{
    /**
     * Google Play package name
     */
    protected ?string $packageName;

    /**
     * Log channel to use
     */
    protected string $logChannel = 'google-play';

    /**
     * Whether in development environment
     */
    protected bool $isDev = false;

    protected GooglePlayApiClientInterface $apiClient;

    protected GooglePlayPriceValidator $priceValidator;

    protected GooglePlayIdGenerator $idGenerator;

    /**
     * Constructor
     */
    public function __construct(
        GooglePlayApiClientInterface $apiClient,
        GooglePlayPriceValidator $priceValidator,
        GooglePlayIdGenerator $idGenerator
    ) {
        $this->apiClient = $apiClient;
        $this->priceValidator = $priceValidator;
        $this->idGenerator = $idGenerator;
        $this->packageName = config('google-play.package_name');
        $this->isDev = in_array(app()->environment(), ['local', 'development', 'testing']);

        if (empty($this->packageName)) {
            $this->logWarning('Google Play package name not configured');
        }
    }

    /**
     * Generate a unique ID with environment prefix
     *
     * @param  string  $type  The type of entity (product, subscription, offer)
     * @param  string  $name  The name to incorporate
     * @return string The generated unique ID
     */
    protected function generateUniqueId(string $type, string $name): string
    {
        return $this->idGenerator->generate($type, $name);
    }

    /**
     * Validate price against Google Play constraints
     *
     * @param  int  $priceMicros  Price in micros
     * @param  int  $planId  Plan ID for logging
     *
     * @throws Exception if price is out of bounds
     */
    protected function validatePrice(int $priceMicros, int $planId): void
    {
        $this->priceValidator->validate($priceMicros, $planId);
    }

    /**
     * Make API request to Google Play with proper logging
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE)
     * @param  string  $endpoint  API endpoint
     * @param  array  $data  Request data
     * @param  array  $context  Additional context for logging
     * @return array Response data and status
     *
     * @throws Exception
     */
    protected function makeApiRequest(string $method, string $endpoint, array $data = [], array $context = []): array
    {
        $this->logInfo('API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'context' => $context,
        ]);

        $result = $this->apiClient->makeRequest($method, $endpoint, $data, $context);

        if ($this->isDev && ! $result['success']) {
            $this->logDebug('API response', $result);
        }

        return $result;
    }

    /**
     * Standardized method to fetch data from a Google Play endpoint
     */
    protected function fetchFromEndpoint(string $endpoint, string $context, ?string $dataKey = null): array
    {
        try {
            $result = $this->makeApiRequest('get', $endpoint, [], [
                'context' => $context,
            ]);

            if (! $result['success']) {
                $this->logWarning("Failed to fetch data from {$endpoint}", [
                    'context' => $context,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                return [];
            }

            // If dataKey is specified, extract that specific subset of data
            if ($dataKey !== null) {
                return $result['data'][$dataKey] ?? [];
            }

            return $result['data'] ?? [];
        } catch (Exception $e) {
            $this->logError("Exception while fetching from {$endpoint}", [
                'context' => $context,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Handle common API errors and provide standardized response
     *
     * @param  Exception  $e  The exception that occurred
     * @param  string  $context  Context of the operation
     * @param  array  $meta  Additional metadata
     * @return array Standardized error response
     */
    protected function handleApiException(Exception $e, string $context, array $meta = []): array
    {
        $this->logError("API exception: {$context}", [
            'exception' => $e->getMessage(),
            'meta' => $meta,
        ]);

        return [
            'success' => false,
            'status' => 500,
            'error' => "Error: {$e->getMessage()}",
            'context' => $context,
        ];
    }

    /*
     * Standardized logging methods
     */

    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->info($message, $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->error($message, $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->warning($message, $context);
    }

    protected function logDebug(string $message, array $context = []): void
    {
        if ($this->isDev) {
            Log::channel($this->logChannel)->debug($message, $context);
        }
    }
}
