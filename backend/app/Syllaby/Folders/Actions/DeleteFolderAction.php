<?php

namespace App\Syllaby\Folders\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Resource;
use App\Syllaby\Folders\Jobs\RemoveResourceFromStorage;

class DeleteFolderAction
{
    /**
     * Handles the removal of specified resources for a given user.
     */
    public function handle(User $user, array $resources, bool $deleteUnusedAssets = false): void
    {
        Resource::query()->with('model')
            ->whereIn('id', $resources)
            ->where('user_id', $user->id)
            ->each(fn (Resource $resource) => dispatch(new RemoveResourceFromStorage($resource, $deleteUnusedAssets)));
    }
}
