<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Individual;

use Arr;
use Exception;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Client\PendingRequest;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use Laravel\Socialite\Two\AbstractProvider as SocialiteProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class TikTokProvider extends AbstractProvider
{
    public function callback(): SocialAccount
    {
        $url = request()->query('redirect_url', 'invalid-url');

        if (! preg_match($this->getUrlValidationPattern(), $url)) {
            throw new Exception(__('social.invalid_url'));
        }

        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        $socialiteUser = $driver->redirectUrl($url)->stateless()->user();

        $this->accountExists($this->provider(), $socialiteUser, auth('sanctum')->user());

        return $this->upsertAccount($this->provider(), $socialiteUser);
    }

    public function disconnect(SocialChannel $channel): bool
    {
        $response = $this->http()->post('/oauth/revoke/', $this->disconnectPayload($channel->account));

        if (Arr::has($response->json(), 'error') && $response->json('error') != 'invalid_grant') {
            return false;
        }

        return $response->successful();
    }

    public function refresh(SocialAccount $account): SocialAccount
    {
        if ($account->needs_reauth) {
            throw new InvalidRefreshTokenException;
        }

        $response = $this->http()->post('/oauth/token/', $this->refreshPayload($account));

        if ($response->json('error') == 'invalid_grant' || blank($response->json('access_token'))) {
            $account->update(['needs_reauth' => true, 'expires_in' => 0]);
            throw new InvalidRefreshTokenException;
        }

        return tap($account)->update([
            'access_token' => $response->json('access_token'),
            'expires_in' => $response->json('expires_in'),
            'refresh_token' => $response->json('refresh_token'),
            'refresh_expires_in' => $response->json('refresh_expires_in'),
            'needs_reauth' => false,
            'errors' => null,
        ]);
    }

    public function redirect(string $redirectUrl): string
    {
        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        return $driver->stateless()
            ->setScopes(config("services.{$this->provider()->toString()}.scopes"))
            ->redirectUrl($redirectUrl)
            ->redirect()
            ->getTargetUrl();
    }

    public function getUrlValidationPattern(): string
    {
        return '/https:\/\/(dev-ai|stg-ai|ai)\.syllaby\.(dev|io)\/(social-connect\/tiktok|calendar)/';
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::TikTok;
    }

    public function validate(SocialChannel $channel): bool
    {
        $response = Http::withToken($channel->account->access_token)->get('https://open.tiktokapis.com/v2/user/info/?fields=display_name');

        return $response->successful();
    }

    private function upsertAccount(SocialAccountEnum $provider, User $socialiteUser): SocialAccount
    {
        return attempt(function () use ($provider, $socialiteUser) {
            return tap($this->syncAccount($provider, $socialiteUser), fn ($account) => $this->syncChannel($account, $socialiteUser));
        });
    }

    private function disconnectPayload(SocialAccount $account): array
    {
        return [
            'client_key' => config('services.tiktok.client_id'),
            'client_secret' => config('services.tiktok.client_secret'),
            'token' => $account->access_token,
        ];
    }

    private function refreshPayload(SocialAccount $account): array
    {
        return [
            'client_key' => config('services.tiktok.client_id'),
            'client_secret' => config('services.tiktok.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(config('services.tiktok.base_url'))->asForm();
    }

    private function syncAccount(SocialAccountEnum $provider, SocialiteUser $socialiteUser): SocialAccount
    {
        return auth('sanctum')->user()->socialAccounts()->updateOrCreate([
            'provider' => $provider->value,
            'provider_id' => $socialiteUser->getId(),
        ], [
            'access_token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'expires_in' => $socialiteUser->expiresIn,
            'refresh_expires_in' => floor(now()->diffInSeconds(now()->addYear())),
            'needs_reauth' => false,
        ]);
    }

    private function syncChannel(SocialAccount $account, SocialiteUser $user): SocialAccount
    {
        $channel = $account->channels()->updateOrCreate([
            'provider_id' => $user->getId(),
        ], [
            'name' => $user->getName(),
            'type' => SocialChannel::INDIVIDUAL,
        ]);

        if (is_null($user->getAvatar())) {
            return $account;
        }

        $media = app(TransloadMediaAction::class)->handle($channel, $user->getAvatar(), 'avatar');

        $channel->update(['avatar' => $media->getFullUrl()]);

        return $account;
    }
}
