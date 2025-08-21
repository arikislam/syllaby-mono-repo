<?php

namespace App\Syllaby\Folders\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Folders\Resource;

class AddToRootAction
{
    public function handle(Video $video, User $user): Resource
    {
        $parent = Resource::where('user_id', $user->id)
            ->where('model_type', 'folder')
            ->whereNull('parent_id')
            ->sole();

        return $video->resource()->create(['user_id' => $user->id, 'parent_id' => $parent->id]);
    }
}
