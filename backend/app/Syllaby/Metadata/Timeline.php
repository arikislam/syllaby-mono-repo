<?php

namespace App\Syllaby\Metadata;

use App\Syllaby\Users\User;
use Database\Factories\TimelineFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timeline extends Model
{
    use HasFactory;

    /**
     * The attributes that are not mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the model that the timeline belongs to.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that the caption belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Timeline $timeline) {
            $timeline->hash = $timeline->rehash($timeline->content);
        });
    }

    /**
     * Ensure same method is always used to hash provided source.
     */
    public function rehash(array $content): string
    {
        return hash('sha256', json_encode($content));
    }

    /**
     * Get the casts for the model.
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    /**
     * Get the factory for the model.
     */
    protected static function newFactory(): Factory
    {
        return TimelineFactory::new();
    }
}
