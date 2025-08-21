<?php

namespace App\Syllaby\Folders;

use App\Syllaby\Users\User;
use Database\Factories\FolderFactory;
use App\Syllaby\Bookmarks\Bookmarkable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Folder extends Model
{
    use Bookmarkable, HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): MorphOne
    {
        return $this->morphOne(Resource::class, 'model');
    }

    protected static function newFactory(): Factory
    {
        return FolderFactory::new();
    }
}
