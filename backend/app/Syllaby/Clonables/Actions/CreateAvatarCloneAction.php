<?php

namespace App\Syllaby\Clonables\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Clonables\Enums\CloneStatus;

class CreateAvatarCloneAction
{
    /**
     * Creates the user intent of cloning an avatar.
     */
    public function handle(User $user, array $input): Clonable
    {
        return Clonable::create([
            'user_id' => $user->id,
            'status' => CloneStatus::PENDING,
            'model_type' => (new Avatar)->getMorphClass(),
            'metadata' => Arr::only($input, ['provider', 'name', 'gender', 'url']),
        ]);
    }
}
