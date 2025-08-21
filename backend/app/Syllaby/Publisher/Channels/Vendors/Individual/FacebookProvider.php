<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Individual;

use Log;
use Http;
use Laravel;
use Exception;
use Illuminate\Http\Client\Response;
use GuzzleHttp\Promise\PromiseInterface;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\DTOs\MetaPageData;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Jobs\ExtractFacebookProfilePic;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class FacebookProvider extends AbstractProvider
{
    const array REQUIRED_ROLES = ['CREATE_CONTENT', 'MANAGE', 'MODERATE'];

    public function redirect(string $redirectUrl): string
    {
        /** @var Laravel\Socialite\Two\FacebookProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        return $driver->stateless()
            ->usingGraphVersion(config('services.facebook.graph_version'))
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

        /** @var Laravel\Socialite\Two\FacebookProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        $socialiteUser = $driver->redirectUrl($url)->stateless()->user();

        $this->accountExists($this->provider(), $socialiteUser, auth('sanctum')->user());

        $account = $this->upsertAccountAndChannels($socialiteUser);

        return $account->load('channels');
    }

    public function disconnect(SocialChannel $channel): bool
    {
        $account = $channel->account;

        $response = Http::meta()->delete("{$account->provider_id}/permissions?access_token={$account->access_token}");

        if ($this->alreadyRevokedOrExpired($response) || $response->successful()) {
            return true;
        }

        return tap(false, function () use ($response, $channel) {
            Log::error("Failed to disconnect Facebook channel {$channel->id}", [$response->json()]);
        });
    }

    public function getUrlValidationPattern(): string
    {
        return '/https:\/\/(dev-ai|stg-ai|ai)\.syllaby\.(dev|io)\/(social-connect\/facebook|calendar)/';
    }

    public function refresh(SocialAccount $account): SocialAccount
    {
        $account->update(['needs_reauth' => true, 'expires_in' => 0]);

        throw new InvalidRefreshTokenException("`{$this->provider()->toString()}` doesn't support refreshing tokens.");
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::Facebook;
    }

    public function validate(SocialChannel $channel): bool
    {
        try {
            return (bool) Http::meta()->get('/debug_token', [
                'input_token' => $channel->access_token,
                'access_token' => config('services.facebook.client_id').'|'.config('services.facebook.client_secret'),
            ])->throw()->json('data.is_valid');
        } catch (Exception $e) {
            Log::alert("Failed to validate Facebook channel {$channel->id}", [$e->getMessage()]);

            return false;
        }
    }

    private function upsertAccountAndChannels(SocialiteUser $socialite): SocialAccount
    {
        return attempt(function () use ($socialite) {
            return tap($this->syncAccount($socialite), fn ($account) => $this->syncPages($account, $socialite));
        });
    }

    private function syncAccount(SocialiteUser $socialite)
    {
        return auth('sanctum')->user()->socialAccounts()->updateOrCreate([
            'provider' => $this->provider()->value,
            'provider_id' => $socialite->getId(),
        ], [
            'access_token' => $socialite->token,
            'expires_in' => $socialite->expiresIn,
            'needs_reauth' => false,
        ]);
    }

    protected function syncPages(SocialAccount $account, SocialiteUser $socialite): SocialAccount
    {
        $channels = collect($this->pagesFor($socialite))
            ->filter($this->rejectNonAdmins())
            ->map(fn (MetaPageData $page) => [
                'social_account_id' => $account->id,
                'provider_id' => $page->id,
                'name' => $page->name,
                'access_token' => $page->token,
                'type' => SocialChannel::PAGE,
            ])->toArray();

        $account->channels()->upsert($channels, ['provider_id'], ['name', 'type', 'access_token']);

        dispatch(new ExtractFacebookProfilePic($account));

        return $account;
    }

    protected function pagesFor(SocialiteUser $socialite): array
    {
        $pages = [];
        $url = sprintf('%s/accounts?access_token=%s', $socialite->getId(), $socialite->token);

        do {
            $response = Http::meta()->get($url);

            if ($response->failed()) {
                Log::error("Failed to fetch Facebook pages for user {$socialite->getId()}", [$response->json()]);

                return [];
            }

            $pages = array_merge($pages, collect($response->json('data'))->map(fn ($page) => new MetaPageData(
                id: $page['id'],
                name: $page['name'],
                token: $page['access_token'],
                roles: $page['tasks']
            ))->toArray());

        } while ($url = $response->json('paging.next'));

        return $pages;
    }

    private function alreadyRevokedOrExpired(PromiseInterface|Response $response): bool
    {
        return $response->json('error.code') === 190;
    }

    public function rejectNonAdmins(): callable
    {
        return fn (MetaPageData $page) => empty(array_diff(self::REQUIRED_ROLES, $page->roles));
    }
}
