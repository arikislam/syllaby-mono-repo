<?php

namespace App\Syllaby\Planner;

use App\Syllaby\Users\User;
use Database\Factories\EventFactory;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Get only the events for the given user.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Event owner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Event related scheduler.
     */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(Scheduler::class);
    }

    /**
     * Get the parent event model (video, article, etc).
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return EventFactory::new();
    }
}
