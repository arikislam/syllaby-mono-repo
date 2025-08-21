<?php

namespace App\Syllaby\Publisher\Metrics\Services;

use Log;
use Google;
use Illuminate\Support\Collection;
use Google\Service\YouTube\VideoListResponse;

class YoutubeMetricsService
{
    public function fetch(Collection $publications): ?VideoListResponse
    {
        $client = app(Google\Client::class);

        $client->setAccessToken($publications->first()->channel->account->access_token);

        $youtube = new \Google_Service_YouTube($client);

        try {
            return $youtube->videos->listVideos('snippet,statistics', [
                'id' => implode(',', $publications->pluck('provider_media_id')->toArray()),
            ]);
        } catch (\Google_Exception $e) {
            match (data_get($e->getErrors()[0], 'message')) {
                'Invalid Credentials' => Log::alert("Expired Credentials in fetching Youtube Metrics for {$publications->first()->channel->name}"),
                default => Log::alert('Request Failed', [$e->getMessage()])
            };

            return null;
        }
    }
}
