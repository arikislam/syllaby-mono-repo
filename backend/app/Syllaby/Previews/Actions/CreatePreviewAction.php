<?php

namespace App\Syllaby\Previews\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Videos\DTOs\Options;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Videos\Enums\VideoProvider;
use App\Syllaby\Folders\Actions\AddToRootAction;

class CreatePreviewAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(protected AddToRootAction $folders) {}

    /**
     * Creates a new video record and renders it.
     */
    public function handle(User $user, array $input): Video
    {
        return tap($this->createVideo($user), function (Video $video) use ($user, $input) {
            $video->setRelation('faceless', $this->createFaceless($video, $input));

            $this->folders->handle($video, $user);
        });
    }

    /**
     * Creates a new video record for the user.
     */
    private function createVideo(User $user): Video
    {
        return Video::create([
            'user_id' => $user->id,
            'type' => Video::FACELESS,
            'status' => VideoStatus::RENDERING,
            'title' => 'My First Syllaby Video',
            'provider' => VideoProvider::CREATOMATE->value,
        ]);
    }

    /**
     * Creates a new faceless record for the video.
     */
    private function createFaceless(Video $video, array $input): Faceless
    {
        return $video->faceless()->create([
            'user_id' => $video->user_id,
            'genre_id' => Arr::get($input, 'genre_id'),
            'script' => Arr::get($input, 'script'),
            'type' => FacelessType::AI_VISUALS->value,
            'voice_id' => Arr::get($input, 'voice_id'),
            'estimated_duration' => Arr::get($input, 'duration'),
            'music_id' => Arr::get($input, 'music_id'),
            'options' => new Options(
                font_family: Arr::get($input, 'captions.font_family'),
                position: Arr::get($input, 'captions.position'),
                aspect_ratio: Arr::get($input, 'aspect_ratio'),
                sfx: Arr::get($input, 'sfx'),
                font_color: Arr::get($input, 'captions.font_color'),
                volume: Arr::get($input, 'volume'),
                transition: Arr::get($input, 'transition'),
                caption_effect: Arr::get($input, 'captions.effect'),
                overlay: Arr::get($input, 'overlay'),
            ),
        ]);
    }
}
