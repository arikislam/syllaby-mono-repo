<?php

namespace App\Syllaby\Ideas\Contracts;

use App\Syllaby\Users\User;

interface IdeaDiscovery
{
    public function search(string $keyword, array $input, User $user);
}
