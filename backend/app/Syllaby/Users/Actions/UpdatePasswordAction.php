<?php

namespace App\Syllaby\Users\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Auth\Notifications\PasswordUpdatedConfirmation;

class UpdatePasswordAction
{
    public function handle(array $request)
    {
        $user = auth('sanctum')->user();
        $password = data_get($request, 'password');

        return tap($user, function ($user) use ($password) {
            $user->update(['password' => $password]);
            $this->sendPasswordUpdatedEmail($user);
        });
    }

    private function sendPasswordUpdatedEmail(User $user): void
    {
        try {
            $user->notify(new PasswordUpdatedConfirmation);
        } catch (Exception $exception) {
            Log::alert('Error sending reset password to {email}', [
                'email' => $user->email,
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
