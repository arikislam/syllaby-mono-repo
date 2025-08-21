<?php

namespace App\Syllaby\Bookmarks;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Bookmarkable
{
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'model');
    }

    public function isBookmarkedBy(User $user): bool
    {
        return $this->bookmarks->where('user_id', $user->id)->exists();
    }

    public function scopeBookmarked(Builder $query, User $user): Builder
    {
        return $query->whereHas('bookmarks', fn ($query) => $query->where('user_id', $user->id));
    }
}
