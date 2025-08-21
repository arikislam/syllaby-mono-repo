<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Actions\UploadMediaAction;
use App\Syllaby\Publisher\Publications\Publication;

class CreatePublicationAction
{
    public function __construct(protected UploadMediaAction $upload) {}

    public function handle(array $input, User $user): Publication
    {
        $publication = $user->publications()->create(['name' => Arr::get($input, 'name')]);

        if ($this->isSyllabyVideo($input)) {
            $publication->fill(['video_id' => $input['video_id']]);
        }

        if ($this->isCustomVideo($input)) {
            $this->upload->handle($publication, $input['files'], 'publications');
        }

        return tap($publication)->update(['draft' => true]);
    }

    private function isSyllabyVideo(array $input): bool
    {
        return Arr::has($input, 'video_id');
    }

    private function isCustomVideo(array $input): bool
    {
        return Arr::has($input, 'files');
    }
}
