<?php

namespace App\Syllaby\Videos\Policies;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use Illuminate\Auth\Access\Response;
use App\Http\Responses\ErrorCode as Code;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\Users\Actions\CalculateStorageAction;

class VideoPolicy
{
    use HandlesAuthorization;

    const int SAFE_SPACE = 100_000_000; // 100MB

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Video $video): bool
    {
        return $user->owns($video);
    }

    /**
     * Determine whether the user can create the model.
     */
    public function create(User $user, Video $video): bool
    {
        return $user->owns($video);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Video $video): Response
    {
        return match (true) {
            ! $user->owns($video) => Response::deny('You are not allowed to update this video.', Code::GEN_FORBIDDEN->value),
            $video->isBusy() => Response::deny('The video is processing currently. Please try once it is finished', Code::GEN_FORBIDDEN->value),
            default => Response::allow(),
        };
    }

    /**
     * Determine whether the user can render the video.
     *
     * @throws Exception
     */
    public function render(User $user, Video $video, string $type): Response
    {
        return match (true) {
            ! $user->owns($video) => Response::deny('You are not allowed to render this video', Code::GEN_FORBIDDEN->value),
            ! $this->hasEnoughStorage($user) => Response::deny('Please remove some files first', Code::REACH_PLAN_STORAGE_LIMIT->value),
            $video->isBusy() => Response::deny('The video is processing currently. Please try again once finished', Code::GEN_FORBIDDEN->value),
            $this->hasUncompletedClone($video) => Response::deny('One or more real clone have not generated successfully', Code::GEN_FORBIDDEN->value),
            default => Response::allow(),
        };
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Video $video): bool
    {
        $blockers = match (true) {
            $this->hasUnfinishedClone($video) => true,
            default => false
        };

        return ! $blockers && ! $video->isBusy() && $user->owns($video);
    }

    /**
     * Checks if the user has enough storage to generate a Digital Twin.
     *
     * @throws Exception
     */
    private function hasEnoughStorage(User $user): bool
    {
        $allowed = (int) Feature::value('max_storage');

        $used = app(CalculateStorageAction::class)->handle($user);

        return ($allowed - self::SAFE_SPACE) >= $used;
    }

    /**
     * Checks if the given video has any failed clones.
     */
    private function hasUncompletedClone(Video $video): bool
    {
        return $video->clones()->whereIn('status', [
            RealCloneStatus::FAILED, RealCloneStatus::SYNC_FAILED,
            RealCloneStatus::SYNCING, RealCloneStatus::GENERATING,
        ])->exists();
    }

    /**
     * Checks if the video has unfinished real clones.
     */
    private function hasUnfinishedClone(Video $video): bool
    {
        return $video->clones()->whereIn('status', [
            RealCloneStatus::SYNCING, RealCloneStatus::GENERATING,
        ])->exists();
    }
}
