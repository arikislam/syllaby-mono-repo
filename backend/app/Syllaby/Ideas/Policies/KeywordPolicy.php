<?php

namespace App\Syllaby\Ideas\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Auth\Access\HandlesAuthorization;

class KeywordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Keyword $keyword): bool
    {
        return $user->keywords()->where('keywords.id', $keyword->id)->exists();
    }

}
