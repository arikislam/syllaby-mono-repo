<?php

namespace App\Syllaby\RealClones\Policies;

use Exception;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Illuminate\Auth\Access\Response;
use App\Syllaby\RealClones\RealClone;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Users\Actions\CalculateStorageAction;

class RealClonePolicy
{
    const int SAFE_SPACE = 104_857_600; // 100MB

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RealClone $clone): bool
    {
        return $user->owns($clone);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RealClone $clone): bool
    {
        return $user->owns($clone);
    }

    /**
     * Determine whether the user can generate the real clone.
     *
     * @throws Exception
     */
    public function generate(User $user, RealClone $clone): Response
    {
        if (! $this->hasEnoughStorage($user)) {
            return Response::deny('Please remove some files first.', Code::REACH_PLAN_STORAGE_LIMIT->value);
        }

        if ($user->owns($clone) && filled($clone->provider) && $clone->isFinished()) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RealClone $clone): bool
    {
        return $user->owns($clone) && $clone->isFinished();
    }

    /**
     * Checks if the user has enough storage to generate a real clone.
     *
     * @throws Exception
     */
    private function hasEnoughStorage(User $user): bool
    {
        $allowed = (int) Feature::for($user)->value('max_storage');
        $used = app(CalculateStorageAction::class)->handle($user);

        return ($allowed - self::SAFE_SPACE) >= $used;
    }
}
