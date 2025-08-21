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

class LinkedInProvider extends AbstractProvider
{
    public function __construct(protected TransloadMediaAction $media) {}

    public function callback(): SocialAccount
    {
        $url = request()->query('redirect_url', 'invalid-url');

        if (! preg_match($this->getUrlValidationPattern(), $url)) {
            throw new Exception('Invalid redirect URL');
        }

        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        $socialiteUser = $driver->redirectUrl($url)->stateless()->user();

        $this->accountExists($this->provider(), $socialiteUser, auth('sanctum')->user());

        return $this->upsertAccount($this->provider(), $socialiteUser);
    }

    public function disconnect(SocialChannel $channel): bool
    {
        $response = $this->http()->post('/revoke', $this->disconnectPayload($channel->account));

        return $response->successful() || $response->status() === 400;
    }

    public function refresh(SocialAccount $account): SocialAccount
    {
        $response = $this->http()->post('/accessToken', $this->refreshPayload($account));

        if ($response->clientError()) {
            $account->update(['needs_reauth' => true, 'expires_in' => 0]);
            throw new InvalidRefreshTokenException;
        }

        return tap($account)->update([
            'access_token' => $response->json('access_token'),
            'expires_in' => $response->json('expires_in'),
            'refresh_token' => $response->json('refresh_token'),
            'refresh_expires_in' => $response->json('refresh_token_expires_in'),
            'needs_reauth' => false,
            'errors' => null,
        ]);
    }

    public function getUrlValidationPattern(): string
    {
        return '/https:\/\/(dev-ai|stg-ai|ai)\.syllaby\.(dev|io)\/(social-connect\/linkedin)/';
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::LinkedIn;
    }

    public function redirect(string $redirectUrl): string
    {
        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver('linkedin');

        return $driver->stateless()
            ->setScopes(config('services.linkedin.scopes'))
            ->redirectUrl($redirectUrl)
            ->redirect()
            ->getTargetUrl();
    }

    public function validate(SocialChannel $channel): bool
    {
        $response = Http::get("https://api.linkedin.com/v2/me?oauth2_access_token={$channel->account->access_token}");

        return $response->ok();
    }

    private function upsertAccount(SocialAccountEnum $provider, User $socialiteUser): SocialAccount
    {
        return attempt(function () use ($provider, $socialiteUser) {
            return tap($this->syncAccount($provider, $socialiteUser), fn ($account) => $this->syncChannel($account, $socialiteUser));
        });
    }

    public function disconnectPayload(SocialAccount $account): array
    {
        return [
            'client_id' => config('services.linkedin.client_id'),
            'client_secret' => config('services.linkedin.client_secret'),
            'token' => $account->access_token,
        ];
    }

    public function refreshPayload(SocialAccount $account): array
    {
        return [
            'client_id' => config('services.linkedin.client_id'),
            'client_secret' => config('services.linkedin.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl('https://www.linkedin.com/oauth/v2/')->asForm();
    }

    private function syncAccount(SocialAccountEnum $provider, SocialiteUser $socialiteUser): SocialAccount
    {
        return auth('sanctum')->user()->socialAccounts()->updateOrCreate([
            'provider' => $provider->value,
            'provider_id' => $socialiteUser->getId(),
        ], [
            'access_token' => $socialiteUser->token,
            'expires_in' => $socialiteUser->expiresIn,
            'refresh_token' => $socialiteUser->refreshToken,
            'refresh_expires_in' => Arr::get($socialiteUser->getRaw(), 'refresh_token_expires_in'),
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

        $media = $this->media->handle($channel, $user->getAvatar(), 'avatar');

        $channel->update(['avatar' => $media->getFullUrl()]);

        return $account;
    }
}
