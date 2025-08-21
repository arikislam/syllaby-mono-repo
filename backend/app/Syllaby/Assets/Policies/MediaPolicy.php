<?php

namespace App\Syllaby\Assets\Policies;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use Illuminate\Auth\Access\Response;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Users\Actions\CalculateStorageAction;

class MediaPolicy
{
    /**
     * Create new policy instance.
     */
    public function __construct(protected CalculateStorageAction $storage)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function upload(User $user, int $size): Response
    {
        if (! $this->hasEnoughStorage($user, $size)) {
            return Response::deny('Please remove some files first.', Code::REACH_PLAN_STORAGE_LIMIT->value);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media): bool
    {
        return $user->owns($media, 'model_id') && $user->getMorphClass() === $media->model_type;
    }

    /**
     * Calculates if the user has enough storage to upload the given file.
     */
    private function hasEnoughStorage(User $user, int $size): bool
    {
        $allowed = (int) Feature::value('max_storage');

        $used = $this->storage->handle($user);

        return $allowed >= ($used + $size);
    }
}
