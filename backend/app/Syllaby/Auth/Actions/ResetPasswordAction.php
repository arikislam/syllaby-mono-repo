<?php

namespace App\Syllaby\Auth\Actions;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function __construct(protected PasswordBroker $broker)
    {
    }

    /** @throws Throwable|ValidationException */
    public function handle(array $request)
    {
        $user = User::query()->where('email', $request['email'])->first();

        throw_if($user === null, ValidationException::withMessages([
            'email' => [__('passwords.user')],
        ]));

        $response = $this->broker->reset($request, function ($user, $password) {
            $this->resetPassword($user, $password);
        });

        if ($response === PasswordBroker::PASSWORD_RESET) {
            return $response;
        }

        throw (ValidationException::withMessages(['email' => [__($response)]]));
    }

    private function resetPassword(User $user, $password): void
    {
        $user->password = $password;
        $user->email_verified_at ??= now();
        $user->save();

        event(new PasswordReset($user));
    }
}
