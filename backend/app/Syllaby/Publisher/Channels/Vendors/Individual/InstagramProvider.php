<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Individual;

use Arr;
use Laravel;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\DTOs\MetaPageData;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Jobs\ExtractInstagramProfilePic;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class InstagramProvider extends AbstractProvider
{
    public function __construct(protected FacebookProvider $facebook) {}

    public function redirect(string $redirectUrl): string
    {
        /** @var Laravel\Socialite\Two\FacebookProvider $driver */
        $driver = Socialite::driver('facebook');

        return $driver->stateless()
            ->usingGraphVersion(config('services.facebook.graph_version'))
            ->setScopes([...config('services.facebook.scopes'), ...config('services.facebook.insta_scopes')])
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
        $driver = Socialite::driver('facebook');

        $socialiteUser = $driver->redirectUrl($url)->stateless()->user();

        $this->accountExists($this->provider(), $socialiteUser, auth('sanctum')->user());

        $account = $this->upsertAccountAndChannels($socialiteUser);

        return $account->load('channels');
    }

    public function disconnect(SocialChannel $channel): bool
    {
        return $this->facebook->disconnect($channel);
    }

    public function refresh(SocialAccount $account): SocialAccount
    {
        $account->update(['needs_reauth' => true, 'expires_in' => 0]);

        throw new InvalidRefreshTokenException("`{$this->provider()->toString()}` doesn't support refreshing tokens.");
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::Instagram;
    }

    public function getUrlValidationPattern(): string
    {
        return '/https:\/\/(dev-ai|stg-ai|ai)\.syllaby\.(dev|io)\/(social-connect\/instagram|calendar)/';
    }

    public function validate(SocialChannel $channel): bool
    {
        return $this->facebook->validate($channel);
    }

    private function upsertAccountAndChannels(SocialiteUser $user): SocialAccount
    {
        return attempt(function () use ($user) {
            return tap($this->syncAccount($user), fn (SocialAccount $account) => $this->syncChannels($account, $user));
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

    private function syncChannels(SocialAccount $account, SocialiteUser $user): SocialAccount
    {
        $channels = collect($this->pagesFor($user))
            ->filter($this->facebook->rejectNonAdmins())
            ->map(fn (MetaPageData $page) => [
                'social_account_id' => $account->id,
                'provider_id' => $page->id,
                'name' => $page->name,
                'access_token' => $page->token,
                'type' => SocialChannel::PROFESSIONAL_ACCOUNT,
                'avatar' => $page->avatar,
            ])->toArray();

        if (count($channels) === 0) {
            return $account;
        }

        $account->channels()->upsert($channels, ['provider_id'], ['name', 'type', 'access_token']);

        $avatars = collect($channels)->mapWithKeys(fn ($channel) => [$channel['provider_id'] => $channel['avatar']])->toArray();

        dispatch(new ExtractInstagramProfilePic($account, $avatars));

        return $account;
    }

    private function pagesFor(SocialiteUser $socialite): array
    {
        $pages = [];
        $url = sprintf('%s/accounts', $socialite->getId());

        do {
            $response = Http::meta()->get($url, [
                'fields' => 'id,name,tasks,access_token,instagram_business_account{id,name,username,profile_picture_url}',
                'access_token' => $socialite->token,
            ]);

            if ($response->failed()) {
                Log::error("Failed to fetch Insta Channels for user {$socialite->getId()}", [$response->json()]);

                return [];
            }

            $pages = array_merge($pages, collect($response->json('data'))
                ->filter(fn ($page) => array_key_exists('instagram_business_account', $page))
                ->map(fn ($page) => new MetaPageData(
                    id: Arr::get($page, 'instagram_business_account.id'),
                    name: Arr::get($page, 'instagram_business_account.username'),
                    token: $page['access_token'],
                    roles: $page['tasks'],
                    avatar: Arr::get($page, 'instagram_business_account.profile_picture_url')
                ))->toArray());

        } while ($url = $response->json('paging.next'));

        return $pages;
    }
}
