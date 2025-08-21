<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Individual;

use Arr;
use Exception;
use Google\Client;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use Laravel\Socialite\Two\AbstractProvider as SocialiteProvider;
use App\Syllaby\Publisher\Channels\Services\Youtube\ChannelService;
use App\Syllaby\Publisher\Channels\Exceptions\ChannelNotFoundException;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class YoutubeProvider extends AbstractProvider
{
    public function __construct(protected ChannelService $channel) {}

    public function callback(): SocialAccount
    {
        $url = request()->query('redirect_url', 'invalid-url');

        if (! preg_match($this->getUrlValidationPattern(), $url)) {
            throw new Exception(__('social.invalid_url'));
        }

        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        $socialiteUser = $driver->redirectUrl($url)->stateless()->user();

        $this->ensureYoutubeChannelExists($socialiteUser);

        $this->accountExists($this->provider(), $socialiteUser, auth('sanctum')->user());

        return tap($this->upsertAccount($this->provider(), $socialiteUser), function (SocialAccount $account) {
            $this->channel->updateNameAndAvatar($account->access_token);
        });
    }

    public function disconnect(SocialChannel $channel): bool
    {
        $response = Http::post('https://accounts.google.com/o/oauth2/revoke', [
            'token' => $channel->account->access_token,
        ]);

        return $response->successful() || $response->status() === 400;
    }

    /** @throws InvalidRefreshTokenException */
    public function refresh(SocialAccount $account): SocialAccount
    {
        $client = app(Client::class);

        $client->setClientId(config('services.youtube.client_id'));
        $client->setClientSecret(config('services.youtube.client_secret'));

        $response = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);

        if (Arr::get($response, 'error') == 'invalid_grant' || blank(Arr::get($response, 'access_token'))) {
            $account->update(['needs_reauth' => true, 'expires_in' => 0]);
            throw new InvalidRefreshTokenException;
        }

        return tap($account)->update([
            'access_token' => Arr::get($response, 'access_token'),
            'expires_in' => Arr::get($response, 'expires_in'),
            'refresh_token' => Arr::get($response, 'refresh_token'),
            'needs_reauth' => false,
        ]);
    }

    public function validate(SocialChannel $channel): bool
    {
        $response = Http::get('https://www.googleapis.com/oauth2/v3/tokeninfo', [
            'access_token' => $channel->account->access_token,
        ]);

        return $response->ok() && $response->json('aud') === config('services.youtube.client_id');
    }

    public function getUrlValidationPattern(): string
    {
        return '/https:\/\/(dev-ai|stg-ai|ai)\.syllaby\.(dev|io)\/(social-connect\/youtube|calendar|mobile\/auth\/(youtube))/';
    }

    private function upsertAccount(SocialAccountEnum $provider, User $socialiteUser): SocialAccount
    {
        return attempt(function () use ($provider, $socialiteUser) {
            return tap($this->syncAccount($provider, $socialiteUser), fn ($account) => $this->syncChannels($account, $socialiteUser));
        });
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::Youtube;
    }

    private function ensureYoutubeChannelExists(User $user): void
    {
        $channel = app(ChannelService::class);

        if (! $channel->exists($user->token)) {
            throw new ChannelNotFoundException;
        }
    }

    private function syncAccount(SocialAccountEnum $provider, User $socialiteUser): SocialAccount
    {
        return auth('sanctum')->user()->socialAccounts()->updateOrCreate([
            'provider' => $provider->value,
            'provider_id' => $socialiteUser->getId(),
        ], [
            'access_token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'expires_in' => $socialiteUser->expiresIn,
            'needs_reauth' => false,
        ]);
    }

    private function syncChannels(SocialAccount $account, User $socialiteUser): SocialAccount
    {
        return tap($account, fn (SocialAccount $account) => $account->channels()->updateOrCreate([
            'provider_id' => $socialiteUser->getId(),
        ], [
            'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname(),
            'avatar' => $socialiteUser->getAvatar(),
            'type' => SocialChannel::INDIVIDUAL,
        ]));
    }
}
