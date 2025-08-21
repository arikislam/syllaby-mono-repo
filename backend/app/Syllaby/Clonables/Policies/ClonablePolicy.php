<?php

namespace App\Syllaby\Clonables\Policies;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Auth\Access\Response;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Clonables\Enums\CloneStatus;

class ClonablePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Clonable $clonable): bool
    {
        return $user->owns($clonable);
    }

    /**
     * Determine whether the user can create the model.
     */
    public function create(User $user, string $feature, string $type): Response
    {
        $allowed = (int) Feature::value($feature);
        $used = Clonable::where('user_id', $user->id)->where('model_type', $type)->count();

        if ($used >= $allowed) {
            $code = Code::REACH_PLAN_LIMIT->value;

            return Response::deny('You have reached the cloned voices limit for the current plan', $code);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Clonable $clonable): bool
    {
        return $user->owns($clonable);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Clonable $clonable): bool
    {
        return !in_array($clonable->status, [CloneStatus::PENDING, CloneStatus::REVIEWING]) && $user->owns($clonable);
    }

}
