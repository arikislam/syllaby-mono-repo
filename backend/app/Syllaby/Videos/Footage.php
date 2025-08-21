<?php

namespace App\Syllaby\Videos;

use App\Syllaby\Users\User;
use App\Syllaby\Metadata\Timeline;
use App\Syllaby\RealClones\RealClone;
use Database\Factories\FootageFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Footage extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'preference' => 'array',
        ];
    }

    /**
     * Ensure same method is always used to hash provided source.
     */
    public function rehash(array $source): string
    {
        return md5(serialize($source));
    }

    /**
     * Get the timeline for the footage.
     */
    public function timeline(): MorphOne
    {
        return $this->morphOne(Timeline::class, 'model');
    }

    /**
     * Gets the footage video.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Gets the footage author.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gets the real clones present in the footage.
     */
    public function clones(): HasMany
    {
        return $this->hasMany(RealClone::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return FootageFactory::new();
    }
}
