<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Business;

use Arr;
use Exception;
use App\Syllaby\Users\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Contracts\ChannelContract;
use App\Syllaby\Publisher\Channels\DTOs\LinkedInOrganizationData;
use App\Syllaby\Publisher\Channels\Jobs\ExtractLinkedInProfilePic;
use App\Syllaby\Publisher\Channels\Exceptions\AccountAlreadyConnected;

class LinkedInProvider implements ChannelContract
{
    const string CACHE_KEY = 'linkedin-channels:';

    public function get(): array
    {
        throw_unless($this->invalidQueryParams(), new Exception('Un-supported query params'));

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        $socialiteUser = $driver->stateless()->user();

        $this->accountExists($this->provider(), $socialiteUser, auth('sanctum')->user());

        $user = $this->mapToUser($socialiteUser);

        Cache::put(self::CACHE_KEY.$user['provider_id'], $user, $this->ttl());

        return $this->channelsFor($user);
    }

    public function save(array $input): SocialAccount
    {
        $user = Cache::pull(self::CACHE_KEY.$input['provider_id']);

        throw_if(is_null($user), new Exception('Invalid or Expired Request'));

        $account = $this->upsertAccount($user);

        $this->upsertChannels($input, $account);

        return $account->load('channels.account');
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::LinkedIn;
    }

    public function ttl(): int
    {
        return 10 * 60;
    }

    private function channelsFor(array $user): array
    {
        $response = Http::withHeader('X-Restli-Protocol-Version', '2.0.0')
            ->withToken(Arr::get($user, 'access_token'))
            ->get('https://api.linkedin.com/v2/organizationAcls', $this->queryParams())
            ->throw();

        $channels = $this->rejectNonAdmins($response);

        return LinkedInOrganizationData::fromResponse($channels, $user);
    }

    private function invalidQueryParams(): bool
    {
        return request()->query('type') === 'organization';
    }

    private function mapToUser(SocialiteUser $socialiteUser): array
    {
        return [
            'provider' => $this->provider()->value,
            'provider_id' => $socialiteUser->getId(),
            'name' => $socialiteUser->getName(),
            'avatar' => $socialiteUser->getAvatar(),
            'email' => $socialiteUser->getEmail(), // would always be null
            'access_token' => $socialiteUser->token,
            'expires_in' => $socialiteUser->expiresIn,
            'refresh_token' => $socialiteUser->refreshToken,
            'refresh_expires_in' => Arr::get($socialiteUser->getRaw(), 'refresh_token_expires_in'),
            'needs_reauth' => false,
        ];
    }

    private function queryParams(): array
    {
        return [
            'q' => 'roleAssignee',
            'projection' => '(paging,elements*(role,state,roleAssignee,organization~))',
        ];
    }

    private function rejectNonAdmins(Response $response): array
    {
        return collect($response->json('elements'))
            ->filter(fn ($channel) => $channel['role'] === 'ADMINISTRATOR' && $channel['state'] === 'APPROVED')
            ->values()
            ->toArray();
    }

    private function upsertAccount(array $user): SocialAccount
    {
        return auth('sanctum')->user()->socialAccounts()->updateOrCreate(
            Arr::only($user, ['provider', 'provider_id']),
            Arr::except($user, ['provider', 'provider_id', 'name', 'email', 'avatar'])
        );
    }

    private function upsertChannels(array $input, SocialAccount $account): void
    {
        $channels = collect($input['channels'])->map(fn ($channel) => [
            'social_account_id' => $account->id,
            'provider_id' => Arr::get($channel, 'id'),
            'name' => Arr::get($channel, 'name'),
            'type' => SocialChannel::ORGANIZATION,
        ])->toArray();

        $account->channels()->upsert($channels, ['provider_id'], ['name', 'type', 'avatar']);

        dispatch(new ExtractLinkedInProfilePic($account));
    }

    public function accountExists(SocialAccountEnum $provider, SocialiteUser $socialiteUser, User $user): void
    {
        $exists = SocialAccount::query()
            ->where('user_id', '<>', $user->id)
            ->where('provider', $provider->value)
            ->where('provider_id', $socialiteUser->getId())
            ->exists();

        if ($exists) {
            throw new AccountAlreadyConnected;
        }
    }
}
