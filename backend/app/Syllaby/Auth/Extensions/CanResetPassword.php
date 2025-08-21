<?php

namespace App\Syllaby\Auth\Extensions;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Syllaby\Auth\Notifications\ResetPassword;

/** @mixin Authenticatable&\Illuminate\Contracts\Auth\CanResetPassword */
trait CanResetPassword
{
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($this, $token));
    }
}
