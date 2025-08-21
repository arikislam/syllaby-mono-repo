<?php

namespace App\Syllaby\Schedulers;

use App\Syllaby\Users\User;
use App\Syllaby\Generators\Generator;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\OccurrenceFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Occurrence extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'scheduler_occurrences';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Scheduler creator.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scheduler that the occurrence belongs to.
     */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(Scheduler::class);
    }

    /**
     * Get the ai generator associated with the scheduler.
     */
    public function generator(): MorphOne
    {
        return $this->morphOne(Generator::class, 'model');
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'occurs_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return OccurrenceFactory::new();
    }
}
