<?php

namespace App\Http\Requests\Authentication;

use RateLimiter;
use App\Syllaby\Users\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/** @mixin User */
class LoginRequest extends FormRequest
{
    public const string LOGIN_THROTTLE_PREFIX = 'login-attempt:';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** @throws ValidationException */
    public function ensureIsThrottled(): void
    {
        // Skip throttling for Syllaby test emails
        if (str_contains($this->email, '@syllaby.io')) {
            return;
        }

        $throttleKey = static::LOGIN_THROTTLE_PREFIX.$this->email;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [
                    __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
                ],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($throttleKey);
    }
}
