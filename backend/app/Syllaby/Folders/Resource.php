<?php

namespace App\Syllaby\Folders;

use App\Syllaby\Users\User;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Resource extends Model
{
    use HasFactory;
    use HasRecursiveRelationships;

    protected $guarded = [];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isFolder(): bool
    {
        return $this->model_type === Relation::getMorphAlias(Folder::class);
    }

    public function getParentKeyName(): string
    {
        return 'parent_id';
    }

    protected static function newFactory(): Factory
    {
        return ResourceFactory::new();
    }
}
