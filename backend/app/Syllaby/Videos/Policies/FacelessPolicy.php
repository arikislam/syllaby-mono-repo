<?php

namespace App\Syllaby\Videos\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Faceless;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class FacelessPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Faceless $faceless): bool
    {
        return $user->owns($faceless);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Faceless $faceless): bool
    {
        return $user->owns($faceless);
    }

    /**
     * Determine whether the user can convert the model.
     */
    public function convert(User $user, Faceless $faceless): bool
    {
        return $user->owns($faceless);
    }

    /**
     * Determine if the user can retry the faceless video.
     */
    public function retry(User $user, Faceless $faceless): Response
    {
        if (! $user->owns($faceless)) {
            return $this->deny('You are not allowed to re-generate this video');
        }

        if ($faceless->video->isBusy()) {
            return $this->deny('The video is still being processed. Please wait until it is finished.');
        }

        if ($faceless->video->status !== VideoStatus::FAILED) {
            return $this->deny('Only failed videos can be re-tried. Please create a new video instead.');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can export the faceless video.
     */
    public function export(User $user, Faceless $faceless, string $type): Response
    {
        return match (true) {
            ! $user->owns($faceless) => $this->deny('You are not allowed to export this video'),
            $faceless->video->isBusy() => $this->deny('The video is still being processed'),
            default => Response::allow(),
        };
    }
}
