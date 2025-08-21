<?php

namespace App\Syllaby\Videos\Actions;

use DB;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Videos\Enums\VideoProvider;

class CreateFacelessAction
{
    public function __construct(protected CreateVideoAction $videos) {}

    public function handle(User $user, array $input): Faceless
    {
        return DB::transaction(function () use ($user, $input) {
            $video = $this->videos->handle($user, [
                ...$input,
                'type' => Video::FACELESS,
                'provider' => VideoProvider::CREATOMATE->value,
            ]);

            if (Arr::has($input, 'starts_at')) {
                $this->createEvent($video, $input);
            }

            return $video->faceless()->create([
                'user_id' => $video->user_id,
                'hash' => ['speech' => null, 'options' => null],
                'type' => Arr::get($input, 'type', FacelessType::AI_VISUALS->value),
            ]);
        });
    }

    private function createEvent(Video $video, array $input): void
    {
        $video->event()->create([
            'starts_at' => Arr::get($input, 'starts_at'),
            'ends_at' => Arr::get($input, 'ends_at'),
            'user_id' => $video->user_id,
        ]);
    }
}
