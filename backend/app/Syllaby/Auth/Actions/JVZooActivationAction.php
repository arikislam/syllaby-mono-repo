<?php

namespace App\Syllaby\Auth\Actions;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use App\Syllaby\Subscriptions\JVZooTransaction;

class JVZooActivationAction
{
    /**
     * Handle the activation process for a JVZoo purchase.
     *
     * @throws Throwable|ValidationException
     */
    public function handle(array $input): User
    {
        $transaction = JVZooTransaction::where('onboarding_token', Arr::get($input, 'token'))
            ->where('customer_email', Arr::get($input, 'email'))
            ->where('onboarding_expires_at', '>', now())
            ->whereNull('onboarding_completed_at')
            ->with(['user'])
            ->first();

        throw_if(! $transaction, ValidationException::withMessages([
            'token' => ['Invalid or expired activation token'],
        ]));

        $transaction->update([
            'onboarding_token' => null,
            'onboarding_expires_at' => null,
            'onboarding_completed_at' => now(),
        ]);

        return tap($transaction->user)->update([
            'email_verified_at' => now(),
            'password' => Arr::get($input, 'password'),
        ]);
    }
}
