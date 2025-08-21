<?php

namespace App\Syllaby\Bookmarks\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Topic;
use App\Syllaby\Assets\Asset;
use InvalidArgumentException;
use App\Syllaby\Folders\Folder;
use Illuminate\Database\Eloquent\Model;

class ToggleBookmarkAction
{
    private array $map = [
        Topic::class,
        Asset::class,
        Folder::class,
    ];

    public function handle(User $user, Model $bookmarkable): Model
    {
        if (! in_array(get_class($bookmarkable), $this->map)) {
            throw new InvalidArgumentException("Model of type {$bookmarkable->getMorphClass()} can not be bookmarked.");
        }

        if ($bookmark = $bookmarkable->bookmarks()->where('user_id', $user->id)->first()) {
            $bookmark->delete();
        } else {
            $bookmarkable->bookmarks()->create(['user_id' => $user->id]);
        }

        return $bookmarkable->refresh();
    }
}
