<?php

namespace App\Syllaby\Videos\Actions;

use DB;
use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Folders\Actions\AddToRootAction;

class CreateVideoAction
{
    public function __construct(protected AddToRootAction $action) {}

    /**
     * Creates a video in storage.
     *
     * @throws Exception
     */
    public function handle(User $user, array $input): Video
    {
        if (blank(Arr::get($input, 'type'))) {
            throw new Exception('Video type is required');
        }

        return DB::transaction(function () use ($user, $input) {
            return tap($this->createVideo($user, $input), fn (Video $video) => $this->action->handle($video, $user));
        });
    }

    private function createVideo(User $user, array $input): Video
    {
        return Video::create([
            'user_id' => $user->id,
            'status' => VideoStatus::DRAFT,
            'type' => Arr::get($input, 'type'),
            'idea_id' => Arr::get($input, 'idea_id'),
            'provider' => Arr::get($input, 'provider'),
            'scheduler_id' => Arr::get($input, 'scheduler_id'),
            'title' => Arr::get($input, 'title', 'Untitled video'),
        ]);
    }
}
