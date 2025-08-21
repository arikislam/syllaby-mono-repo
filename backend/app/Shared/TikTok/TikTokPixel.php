<?php

namespace App\Shared\TikTok;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\GuzzleException;

class TikTokPixel
{
    /**
     * Track events using the TikTok business events API.
     */
    public function track(string $id, string $event, array $data): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Access-Token' => config('services.tiktok.pixel.access_token'),
        ];

        try {
            $this->http()->post('/open_api/v1.3/event/track/', [
                RequestOptions::HEADERS => $headers,
                RequestOptions::JSON => $this->payload($id, $event, $data),
            ]);
        } catch (GuzzleException $exception) {
            return;
        }
    }

    /**
     * Builds the event track payload.
     */
    private function payload(string $id, string $event, array $data): array
    {
        $custom = Arr::get($data, 'custom', []);
        $cookies = Arr::pluck(Arr::get($data, 'cookies.tiktok'), 'value', 'key');

        return [
            'event_source' => Arr::get($data, 'source', 'web'),
            'event_source_id' => config('services.tiktok.pixel.id'),
            'data' => [
                [
                    'event' => $event,
                    'event_id' => $id,
                    'event_time' => now()->timestamp,
                    'user' => [
                        'external_id' => hash('sha256', Arr::get($data, 'user.id')),
                        'email' => hash('sha256', Str::lower(Arr::get($data, 'user.email'))),
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'ttp' => Arr::get($cookies, '_ttp'),
                    ],
                    'page' => [
                        'url' => Arr::get($data, 'url'),
                        'referrer' => request()->header('referer', config('app.frontend_url')),
                    ],
                    'properties' => empty($custom) ? [] : [
                        'content_type' => 'product',
                        'price' => Arr::get($custom, 'amount', 0),
                        'value' => Arr::get($custom, 'amount', 0),
                        'quantity' => Arr::get($custom, 'quantity', 1),
                        'content_ids' => [Arr::get($custom, 'product_id')],
                        'currency' => Arr::get($custom, 'currency', 'USD'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Tiktok Events API setup.
     */
    private function http(): Client
    {
        return new Client([
            'base_uri' => config('services.tiktok.pixel.url'),
        ]);
    }
}
