<?php

namespace App\Syllaby\Auth\Actions;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Users\Enums\UserType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Validation\ValidationException;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;

class RegistrationAction
{
    const string LOCK_PREFIX = 'registration-lock:';

    public function __construct(protected Factory $auth) {}

    /**
     * @throws Throwable
     */
    public function handle(array $request): User
    {
        $result = Cache::lock(self::LOCK_PREFIX.$request['email'], 3)->get(function () use ($request) {
            $user = User::where('email', $request['email'])->first();
            throw_if($user, ValidationException::withMessages([__('auth.processing')]));
            $user = $this->createUser($request);
            event(new Registered($user));

            return tap($user, fn ($user) => $this->auth->guard('sanctum')->setUser($user));
        });

        throw_if($result === false, ValidationException::withMessages([__('auth.processing')]));

        return $result;
    }

    private function defaultSettings(array $overrides = []): array
    {
        return array_merge(config('syllaby.users.settings'), $overrides);
    }

    private function defaultNotifications(): array
    {
        return config('syllaby.users.notifications');
    }

    private function createUser(array $request): User
    {
        return User::query()->create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => $request['password'],
            'user_type' => UserType::CUSTOMER,
            'notifications' => $this->defaultNotifications(),
            'settings' => $this->defaultSettings([
                'mailing_list' => Arr::get($request, 'newsletter', false),
            ]),
            'promo_code' => Arr::get($request, 'promo_code'),
            'pm_exemption_code' => Arr::get($request, 'pm_exemption_code'),
            'registration_code' => Arr::get($request, 'registration_code'),
            'email_verified_at' => Arr::get($request, 'email_verified_at'),
            'subscription_provider' => SubscriptionProvider::fromSource(Arr::get($request, 'source', SubscriptionProvider::SOURCE_WEB)),
        ]);
    }
}
