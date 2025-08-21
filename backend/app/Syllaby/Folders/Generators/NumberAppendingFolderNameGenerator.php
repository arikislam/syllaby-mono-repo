<?php

namespace App\Syllaby\Folders\Generators;

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Contracts\FolderNameGenerator;

class NumberAppendingFolderNameGenerator implements FolderNameGenerator
{
    public function generate(User $user, string $name): string
    {
        $existing = $user->folders()->pluck('name')->toArray();

        $unique = $name;
        $counter = 1;

        while (in_array($unique, $existing)) {
            $unique = sprintf('%s (%d)', $name, $counter);
            $counter++;
        }

        return $unique;
    }
}
