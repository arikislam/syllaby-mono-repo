<?php

namespace App\Syllaby\Presets\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Presets\FacelessPreset;
use Illuminate\Auth\Access\HandlesAuthorization;

class FacelessPresetPolicy
{
    use HandlesAuthorization;

    public function update(User $user, FacelessPreset $preset): bool
    {
        return $user->owns($preset);
    }

    public function delete(User $user, FacelessPreset $preset): bool
    {
        return $user->owns($preset);
    }
}
