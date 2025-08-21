<?php

namespace App\Syllaby\Tags;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use Database\Factories\TagFactory;
use App\Syllaby\Templates\Template;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Include the user owned tags.
     */
    public function scopeOwnedBy(Builder $builder, User $user): Builder
    {
        return $builder->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)->orWhereNull('user_id');
        });
    }

    /**
     * Tag owner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all the templates that are assigned this tag.
     */
    public function templates(): MorphToMany
    {
        return $this->morphedByMany(Template::class, 'taggable')
            ->withTimestamps();
    }

    /**
     * Get all the media that are assigned this tag.
     */
    public function media(): MorphToMany
    {
        return $this->morphedByMany(Media::class, 'taggable')
            ->withTimestamps();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return TagFactory::new();
    }
}
