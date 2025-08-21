<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Individual;

use Http;
use Laravel;
use Exception;
use Carbon\CarbonImmutable;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

/** @see https://developers.facebook.com/docs/threads/get-started/long-lived-tokens */
class ThreadsProvider extends AbstractProvider
{
    public function __construct(protected TransloadMediaAction $media) {}

    public function redirect(string $redirectUrl): string
    {
        /** @var Laravel\Socialite\Two\AbstractProvider $provider */
        $provider = Socialite::driver($this->provider()->toString());

        return $provider->stateless()
            ->setScopes(config("services.{$this->provider()->toString()}.scopes"))
            ->redirectUrl($redirectUrl)
            ->redirect()
            ->getTargetUrl();
    }

    public function callback(): SocialAccount
    {
        $url = request()->query('redirect_url', 'invalid-url');

        if (! preg_match($this->getUrlValidationPattern(), $url)) {
            throw new Exception(__('social.invalid_url'));
        }

        /** @var Laravel\Socialite\Two\AbstractProvider $provider */
        $provider = Socialite::driver($this->provider()->toString());

        $user = $provider->redirectUrl($url)->stateless()->user();

        $this->accountExists($this->provider(), $user, auth('sanctum')->user());

        $user = $this->getLongLivedToken($user);

        $account = $this->upsertAccountAndChannel($user);

        return $account->load('channels');
    }

    public function disconnect(SocialChannel $channel): bool
    {
        // As of now, threads doesn't have any endpoint for revoking access token
        // So, temporarily we would just return true
        return true;
    }

    public function refresh(SocialAccount $account): SocialAccount
    {
        $expiry = CarbonImmutable::createFromTimestamp($account->updated_at->timestamp + $account->expires_in);

        // If the token is already expired, we can't use it to refresh
        // We can only refresh the token if it's active
        // Since the token remains active for 60 days, we try to refresh it
        // on the last day of expiry to avoid unnecessary refreshes
        if ($expiry->isPast()) {
            $account->update(['needs_reauth' => true, 'expires_in' => 0]);
            throw new InvalidRefreshTokenException(__('social.refresh_failed', ['provider' => $this->provider()->toString()]));
        }

        $response = Http::withQueryParameters([
            'grant_type' => 'th_refresh_token',
            'client_secret' => config('services.threads.client_secret'),
            'access_token' => $account->access_token,
        ])->get('https://graph.threads.net/refresh_access_token');

        if ($response->failed()) {
            $account->update(['needs_reauth' => true, 'expires_in' => 0]);
            throw new InvalidRefreshTokenException(__('social.refresh_failed', ['provider' => $this->provider()->toString()]));
        }

        return tap($account)->update([
            'access_token' => $response->json('access_token'),
            'expires_in' => $response->json('expires_in'),
            'needs_reauth' => false,
        ]);
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::Threads;
    }

    public function validate(SocialChannel $channel): bool
    {
        try {
            return Http::get('https://graph.threads.net/v1.0/me', [
                'fields' => 'id,username,threads_profile_picture_url,threads_biography',
                'access_token' => $channel->access_token,
            ])->successful();
        } catch (Exception) {
            return false;
        }
    }

    public function getUrlValidationPattern(): string
    {
        return '/https:\/\/(dev-ai|stg-ai|ai)\.syllaby\.(dev|io)\/(social-connect\/threads|calendar)/';
    }

    private function getLongLivedToken(SocialiteUser $user): SocialiteUser
    {
        $response = Http::withQueryParameters([
            'grant_type' => 'th_exchange_token',
            'client_secret' => config('services.threads.client_secret'),
            'access_token' => $user->token,
        ])->get('https://graph.threads.net/access_token');

        if ($response->failed()) {
            throw new Exception(__('social.failed', ['provider' => $this->provider()->toString()]));
        }

        return $user
            ->setToken($response->json('access_token'))
            ->setExpiresIn($response->json('expires_in'));
    }

    private function upsertAccountAndChannel(SocialiteUser $user): SocialAccount
    {
        return attempt(function () use ($user) {
            return tap($this->syncAccount($user), fn ($account) => $this->syncChannel($account, $user));
        });
    }

    private function syncAccount(SocialiteUser $user): SocialAccount
    {
        return auth('sanctum')->user()->socialAccounts()->updateOrCreate([
            'provider' => $this->provider()->value,
            'provider_id' => $user->getId(),
        ], [
            'access_token' => $user->token,
            'refresh_token' => $user->refreshToken,
            'expires_in' => $user->expiresIn,
            'needs_reauth' => false,
        ]);
    }

    private function syncChannel(SocialAccount $account, SocialiteUser $user): SocialAccount
    {
        $channel = $account->channels()->updateOrCreate([
            'provider_id' => $user->getId(),
        ], [
            'name' => $user->getName() ?? $user->getNickname(),
            'type' => SocialChannel::INDIVIDUAL,
        ]);

        if (is_null($user->getAvatar())) {
            return $account;
        }

        $media = $this->media->handle($channel, $user->getAvatar(), 'avatar');

        $channel->update(['avatar' => $media->getFullUrl()]);

        return $account;
    }
}
