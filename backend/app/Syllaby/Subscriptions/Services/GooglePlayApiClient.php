<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Subscriptions\Contracts\GooglePlayApiClientInterface;

class GooglePlayApiClient implements GooglePlayApiClientInterface
{
    private const int TOKEN_CACHE_TTL = 3000; // 50 minutes

    protected string $baseUrl;

    protected string $packageName;

    protected bool $isDev;

    public function __construct()
    {
        $this->baseUrl = config('google-play.api_url');
        $packageName = config('google-play.package_name');
        
        if (empty($packageName)) {
            throw new Exception('Google Play package name not configured. Set GOOGLE_PLAY_PACKAGE_NAME in your .env file.');
        }
        
        $this->packageName = $packageName;
        $this->isDev = in_array(app()->environment(), ['local', 'development', 'testing']);
    }

    /**
     * @throws Exception
     */
    public function makeRequest(string $method, string $endpoint, array $data = [], array $context = []): array
    {
        $token = $this->getAccessToken();
        $url = $this->buildUrl($endpoint);

        if (isset($context['queryParams']) && is_array($context['queryParams'])) {
            $url .= '?'.http_build_query($context['queryParams']);
        }

        $response = Http::withToken($token)->$method($url, $data);

        if ($response->failed()) {
            return [
                'success' => false,
                'status' => $response->status(),
                'data' => $response->json() ?: $response->body(),
                'error' => $response->json()
                    ? json_encode($response->json(), JSON_PRETTY_PRINT)
                    : $response->body(),
            ];
        }

        return [
            'success' => true,
            'status' => $response->status(),
            'data' => $response->json(),
        ];
    }

    public function getAccessToken(): string
    {
        $cacheKey = 'google_play_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $credentialsJson = config('google-play.credentials_json');
        if (empty($credentialsJson)) {
            throw new Exception('Google Play credentials not configured');
        }

        $credentials = json_decode($credentialsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in Google Play credentials');
        }

        $oauthConfig = config('google-play.oauth');
        $now = time();
        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => $oauthConfig['scope'],
            'aud' => $oauthConfig['audience'],
            'exp' => $now + $oauthConfig['token_ttl'],
            'iat' => $now,
        ];

        $jwt = $this->createJwt($payload, $credentials['private_key']);

        $response = Http::post($oauthConfig['token_url'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $responseData = $response->json();
        if (! isset($responseData['access_token'])) {
            throw new Exception('Failed to get access token');
        }

        $token = $responseData['access_token'];
        Cache::put($cacheKey, $token, self::TOKEN_CACHE_TTL);

        return $token;
    }

    private function buildUrl(string $endpoint): string
    {
        if (str_starts_with($endpoint, $this->baseUrl)) {
            return $endpoint;
        }

        if (str_starts_with($endpoint, '/applications')) {
            return $this->baseUrl.$endpoint;
        }

        if (! str_starts_with($endpoint, '/')) {
            $endpoint = '/'.$endpoint;
        }

        return $this->baseUrl.'/applications/'.$this->packageName.$endpoint;
    }

    private function createJwt(array $payload, string $privateKey): string
    {
        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $segments[] = $this->base64UrlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);

        $signature = '';
        openssl_sign($signingInput, $signature, $privateKey, 'SHA256');
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
