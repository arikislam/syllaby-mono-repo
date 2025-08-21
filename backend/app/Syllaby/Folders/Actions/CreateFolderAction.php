<?php

namespace App\Syllaby\Folders\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Resource;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Syllaby\Folders\Contracts\FolderNameGenerator;

class CreateFolderAction
{
    public function __construct(protected FolderNameGenerator $generator) {}

    public function handle(User $user, array $input): Folder
    {
        $name = $this->generator->generate($user, $input['name']);

        return tap($user->folders()->create(['name' => $name]), function (Folder $folder) use ($input) {
            $folder->setRelation('resource', $this->createResource($folder, $input));
        });
    }

    private function createResource(Folder $folder, array $input): Resource
    {
        return Resource::create([
            'user_id' => $folder->user_id,
            'model_id' => $folder->id,
            'model_type' => Relation::getMorphAlias(Folder::class),
            'parent_id' => $input['parent_id'],
        ]);
    }
}
