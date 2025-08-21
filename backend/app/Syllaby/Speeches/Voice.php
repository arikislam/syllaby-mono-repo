<?php

namespace App\Syllaby\Speeches;

use App\Syllaby\Users\User;
use App\Syllaby\Clonables\Clonable;
use Database\Factories\VoiceFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voice extends Model
{
    use HasFactory;

    const string STANDARD = 'standard';

    const string REAL_CLONE = 'real-clone';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Gets the active voices only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Gets also the voices owned by given user.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereNull('user_id')->orWhere('user_id', $user->id);
    }

    /**
     * Gets also the voices for the given providers.
     */
    public function scopeProviders(Builder $query, array $providers): Builder
    {
        return $query->whereIn('provider', $providers);
    }

    /**
     * User that owns the voice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the voice clone proof.
     */
    public function clone(): MorphOne
    {
        return $this->morphOne(Clonable::class, 'clonable');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return VoiceFactory::new();
    }
}
