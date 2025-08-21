<?php

namespace App\System\Traits;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Illuminate\Support\Facades\DB;

trait HandlesFeatures
{
    /**
     * Update user's features when on plan change.
     */
    protected function refreshFeaturesFor(User $user): void
    {
        $user = $user->refresh();

        DB::transaction(function () use ($user) {
            Feature::for($user)->forget(Feature::defined());
            Feature::for($user)->all();
        }, 4);
    }
}
