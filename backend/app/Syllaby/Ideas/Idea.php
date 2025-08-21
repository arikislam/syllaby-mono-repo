<?php

namespace App\Syllaby\Ideas;

use App\Syllaby\Videos\Video;
use Database\Factories\IdeaFactory;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Idea extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_until' => 'datetime',
            'public' => 'boolean',
        ];
    }

    /**
     * Get the keyword that generated the idea.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Campaigns created around this idea.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Scheduler::class);
    }

    /**
     * Videos created around this idea.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return IdeaFactory::new();
    }
}
