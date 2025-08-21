<?php

namespace App\Syllaby\Auth\Actions;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Validation\ValidationException;

class ForgetPasswordAction
{
    public function __construct(protected PasswordBroker $broker)
    {
    }

    /** @throws Throwable|ValidationException */
    public function handle(array $request): string
    {
        $user = User::query()->where('email', $request['email'])->first();

        throw_if($user === null, $this->makeException('passwords.user'));

        $response = $this->broker->sendResetLink($request);

        throw_if($response !== PasswordBroker::RESET_LINK_SENT, $this->makeException($response));

        return $response;
    }

    private function makeException(string $message): ValidationException
    {
        return ValidationException::withMessages(['email' => [__($message)]]);
    }
}
