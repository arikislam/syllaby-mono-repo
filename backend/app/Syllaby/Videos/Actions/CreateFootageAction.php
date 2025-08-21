<?php

namespace App\Syllaby\Videos\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Videos\Enums\VideoProvider;

class CreateFootageAction
{
    public function __construct(protected CreateVideoAction $videos) {}

    /**
     * Handles the creation of a footage video.
     */
    public function handle(User $user, array $input): Footage
    {
        $video = $this->videos->handle($user, [
            ...$input,
            'type' => Video::CUSTOM,
            'provider' => VideoProvider::CREATOMATE->value,
        ]);

        if (Arr::has($input, 'starts_at')) {
            $this->createEvent($video, $input);
        }

        return $this->createFootage($video);
    }

    /**
     * Creates an event record fot the video if needed.
     */
    private function createEvent(Video $video, array $input): void
    {
        $video->event()->create([
            'starts_at' => Arr::get($input, 'starts_at'),
            'ends_at' => Arr::get($input, 'ends_at'),
            'user_id' => $video->user_id,
        ]);
    }

    /**
     * Creates the default footage for the editor.
     */
    private function createFootage($video): Footage
    {
        $footage = $video->footage()->create([
            'user_id' => $video->user_id,
        ]);

        $timeline = $footage->timeline()->create([
            'user_id' => $video->user_id,
            'content' => [
                'width' => 1280,
                'height' => 720,
                'output_format' => 'mp4',
                'frame_rate' => '25 fps',
                'elements' => [],
            ],
        ]);

        return tap($footage)->setRelation('timeline', $timeline);
    }
}
