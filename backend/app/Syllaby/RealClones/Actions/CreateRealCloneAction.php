<?php

namespace App\Syllaby\RealClones\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\RealClones\Enums\RealCloneStatus;

class CreateRealCloneAction
{
    /**
     * Create in storage a draft digital twin.
     */
    public function handle(User $user, ?string $provider, array $input): RealClone
    {
        return RealClone::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'background' => 'transparent',
            'status' => RealCloneStatus::DRAFT,
            'voice_id' => Arr::get($input, 'voice_id'),
            'avatar_id' => Arr::get($input, 'avatar_id'),
            'footage_id' => Arr::get($input, 'footage_id'),
        ]);
    }
}
