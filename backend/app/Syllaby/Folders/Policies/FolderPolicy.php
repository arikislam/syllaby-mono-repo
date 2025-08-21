<?php

namespace App\Syllaby\Folders\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Folder;

class FolderPolicy
{
    public function update(User $user, Folder $folder): bool
    {
        return $user->owns($folder);
    }
}
