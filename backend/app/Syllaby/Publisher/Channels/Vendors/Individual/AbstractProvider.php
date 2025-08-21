<?php

namespace App\Syllaby\Publisher\Channels\Vendors\Individual;

use Exception;
use App\Syllaby\Users\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use Laravel\Socialite\Two\AbstractProvider as SocialiteProvider;
use App\Syllaby\Publisher\Channels\Exceptions\AccountAlreadyConnected;

abstract class AbstractProvider
{
    /**
     * Handle the callback from the social provider.
     */
    abstract public function callback(): SocialAccount;

    /**
     * Disconnect the social account from the provider.
     */
    abstract public function disconnect(SocialChannel $channel): bool;

    /**
     * Refresh the access token for the social account.
     */
    abstract public function refresh(SocialAccount $account): SocialAccount;

    /**
     * Get the social provider.
     */
    abstract public function provider(): SocialAccountEnum;

    /**
     * Validate the token for the social provider.
     */
    abstract public function validate(SocialChannel $channel): bool;

    /**
     * Regex pattern for validating the URL.
     */
    abstract public function getUrlValidationPattern(): string;

    /**
     * Get the redirect url for the social provider.
     */
    public function redirect(string $redirectUrl): string
    {
        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver($this->provider()->toString());

        return $driver->stateless()
            ->setScopes(config("services.{$this->provider()->toString()}.scopes"))
            ->with(config("services.{$this->provider()->toString()}.params"))
            ->redirectUrl($redirectUrl)
            ->redirect()
            ->getTargetUrl();
    }

    public function accountExists(SocialAccountEnum $provider, SocialiteUser $socialiteUser, User $user): void
    {
        // This checks if a user has already connected a social account through a different account
        // It's a rare edge case, but it can happen. For example, while testing a user connects
        // their one YouTube account to multiple test accounts. This prevents that from happening.
        $exists = SocialAccount::query()
            ->where('user_id', '<>', $user->id)
            ->where('provider', $provider->value)
            ->where('provider_id', $socialiteUser->getId())
            ->exists();

        if ($exists) {
            throw new AccountAlreadyConnected();
        }
    }
}
