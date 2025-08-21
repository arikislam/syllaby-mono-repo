<?php

namespace App\Syllaby\Folders\Actions;

use Arr;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Contracts\FolderNameGenerator;

class UpdateFolderAction
{
    public function __construct(protected FolderNameGenerator $generator) {}

    public function handle(Folder $folder, array $input): Folder
    {
        $name = $this->generator->generate($folder->user, $input['name']);

        return tap($folder)->update(['name' => $name, 'color' => Arr::get($input, 'color')]);
    }
}
