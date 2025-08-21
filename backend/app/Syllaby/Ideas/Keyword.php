<?php

namespace App\Syllaby\Ideas;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Enums\Networks;
use App\Syllaby\Credits\CreditHistory;
use Database\Factories\KeywordFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Keyword extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'network' => Networks::class,
    ];

    /**
     * Ideas generated from keyword.
     */
    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    /**
     * User searching for the keyword.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(KeywordUser::class)
            ->withTimestamps()
            ->as('history');
    }

    public function creditHistories(): MorphMany
    {
        return $this->morphMany(CreditHistory::class, 'creditable');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return KeywordFactory::new();
    }
}
