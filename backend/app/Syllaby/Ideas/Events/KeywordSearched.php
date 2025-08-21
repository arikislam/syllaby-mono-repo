<?php

namespace App\Syllaby\Ideas\Events;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Foundation\Events\Dispatchable;

class KeywordSearched
{
    use Dispatchable;

    public function __construct(public User $user, public Keyword $keyword, public $audience = '')
    {
    }
}
