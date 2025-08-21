<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use Throwable;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Actions\UploadMediaAction;
use App\Syllaby\Publisher\Publications\Publication;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;

class UpdatePublicationAction
{
    public function __construct(protected UploadMediaAction $upload)
    {
    }

    /** @throws FileIsTooBig|FileDoesNotExist|Throwable */
    public function handle(array $input, Publication $publication): Publication
    {
        if ($this->isRealClone($input)) {
            $publication->fill(['video_id' => $input['video_id']]);
        }

        if ($this->isCustomVideo($input)) {
            $this->upload->handle($publication, $input['files'], 'publications');
        }

        return tap($publication, function ($publication) use ($input) {
            $publication->fill(['draft' => true, 'name' => Arr::get($input, 'name')])->save();
        });
    }

    private function isRealClone(array $input): bool
    {
        return Arr::has($input, 'video_id');
    }

    private function isCustomVideo(array $input): bool
    {
        return Arr::has($input, 'files');
    }
}
