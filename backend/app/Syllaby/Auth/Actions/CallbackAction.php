<?php

namespace App\Syllaby\Auth\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Login;
use App\Syllaby\Users\Enums\UserType;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Factory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Auth\Exceptions\EmailAlreadyExists;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class CallbackAction
{
    public function __construct(protected Factory $auth) {}

    /** @throws EmailAlreadyExists */
    public function handle(SocialAccountEnum $provider, ?string $token = null)
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider->toString());

        $user = match ($token) {
            null => $driver->stateless()->user(),
            default => $driver->stateless()->userFromToken($token),
        };

        $user = $this->getUser($provider, $user);

        $user->wasRecentlyCreated ? event(new Registered($user)) : event(new Login('sanctum', $user, false));

        return tap($user, fn ($user) => $this->auth->guard('sanctum')->setUser($user));
    }

    /**
     * @throws EmailAlreadyExists
     */
    private function getUser(SocialAccountEnum $provider, SocialiteUser $socialite): User
    {
        $user = User::query()
            ->where('provider', $provider->value)
            ->where('provider_id', $socialite->getId())
            ->first();

        if ($user) {
            return $user;
        }

        if (User::where('email', $socialite->getEmail())->exists()) {
            throw new EmailAlreadyExists(__('auth.already_registered'));
        }

        $user = User::query()->create($this->values($socialite, $provider));
        if (request()->query('source')) {
            $user->update(['subscription_provider' => SubscriptionProvider::fromSource(request()->query('source', SubscriptionProvider::SOURCE_WEB))]);
        }

        return $user;
    }

    private function values(SocialiteUser $user, SocialAccountEnum $provider): array
    {
        return [
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'password' => Str::random(),
            'provider' => $provider->value,
            'provider_id' => $user->getId(),
            'notifications' => config('syllaby.users.notifications'),
            'settings' => config('syllaby.users.settings'),
            'mailing_list' => false,
            'user_type' => UserType::CUSTOMER,
            'email_verified_at' => now(),
        ];
    }
}
