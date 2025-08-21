<?php

namespace App\Syllaby\Videos\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Vendors\Renders\Studio;
use App\Syllaby\Videos\Jobs\Renders\TriggerFootageRendering;

class RenderFootageAction
{
    /**
     * Initiates the video footage rendering process.
     */
    public function handle(Footage $footage, User $user, array $input): Footage
    {
        $video = tap($footage->video, function ($video) {
            $video->update(['status' => VideoStatus::RENDERING, 'synced_at' => null]);
        });

        $this->charge($user, $video, $input);
        dispatch(new TriggerFootageRendering($video, $footage));

        return tap($footage)->save();
    }

    /**
     * Charge user credits for video footage rendering.
     */
    private function charge(User $user, Video $video, array $input): void
    {
        $video->setRelation('user', $user);
        $duration = Arr::get($input, 'duration');

        Studio::driver($video->provider)->charge($video, $duration);
    }
}
