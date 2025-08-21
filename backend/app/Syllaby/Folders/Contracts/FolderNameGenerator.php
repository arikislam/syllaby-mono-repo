<?php

namespace App\Syllaby\Folders\Contracts;

use App\Syllaby\Users\User;

interface FolderNameGenerator
{
    /**
     * Generate a folder name
     */
    public function generate(User $user, string $name): string;
}
