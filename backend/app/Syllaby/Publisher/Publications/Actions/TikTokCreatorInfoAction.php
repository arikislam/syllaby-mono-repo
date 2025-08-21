<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use Arr;
use Cache;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Publications\DTOs\TikTokCreatorInfoData;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class TikTokCreatorInfoAction
{
    const string CACHE_KEY = 'tiktok.creator_info:';

    public function handle(SocialAccount $account): TikTokCreatorInfoData
    {
        if ($account->needs_reauth || $account->expires_in === 0) {
            return TikTokCreatorInfoData::defaults();
        }

        return Cache::remember(self::CACHE_KEY.$account->id, now()->addMonths(3), function () use ($account) {
            $response = retry(
                times: [1000, 1500, 2000, 2500],
                callback: $this->sendRequest($account),
                when: $this->isServerOrTimeoutError()
            );

            $response = json_decode($response->getBody()->getContents(), true);

            if ($this->invalidOrExpiredToken($response)) {
                $account->update(['needs_reauth' => true, 'expires_in' => 0]);
                throw new InvalidRefreshTokenException('We were unable to fetch your details. Please try re-connecting your tiktok account.'); // Fetch this to config file
            }

            if (Arr::get($response, 'error.code') != 'ok') {
                throw new Exception(Arr::get($response, 'error.message'));
            }

            return TikTokCreatorInfoData::fromResponse($response);
        });
    }

    private function sendRequest(SocialAccount $account): callable
    {
        return function () use ($account) {
            return app(Client::class)->post('https://open.tiktokapis.com/v2/post/publish/creator_info/query/', [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer {$account->access_token}",
                    'Content-Type' => 'application/json; charset=UTF-8',
                ],
            ]);
        };
    }

    private function isServerOrTimeoutError(): callable
    {
        return fn ($exception) => $exception instanceof ConnectException || $exception instanceof ServerException;
    }

    private function invalidOrExpiredToken(mixed $response): bool
    {
        return Arr::get($response, 'error.code') == 'access_token_invalid';
    }
}
