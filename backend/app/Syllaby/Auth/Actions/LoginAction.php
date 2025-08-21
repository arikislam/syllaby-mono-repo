<?php

namespace App\Syllaby\Auth\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Factory;
use App\Syllaby\Auth\Events\UserAuthenticated;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function __construct(protected Factory $auth) {}

    /**
     * @throws ValidationException
     */
    public function handle(array $request): ?User
    {
        $credentials = Arr::only($request, ['email', 'password']);

        if (! Auth::attempt([...$credentials, 'active' => true])) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = Auth::user();

        event(new UserAuthenticated('sanctum', $user, false));

        return tap($user, fn () => $this->auth->guard('sanctum')->setUser($user));
    }
}
