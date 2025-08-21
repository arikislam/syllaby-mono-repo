<?php

namespace App\Syllaby\Folders\Actions;

use App\Syllaby\Folders\Resource;

class MoveToFolderAction
{
    /**
     * Move items to a destination folder.
     */
    public function handle(Resource $destination, array $items): Resource
    {
        Resource::query()->where('user_id', $destination->user_id)->whereIn('id', $items)->update([
            'parent_id' => $destination->id,
        ]);

        return $destination;
    }
}
