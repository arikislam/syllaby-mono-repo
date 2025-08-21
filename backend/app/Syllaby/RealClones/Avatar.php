<?php

namespace App\Syllaby\RealClones;

use App\Syllaby\Users\User;
use Spatie\MediaLibrary\HasMedia;
use App\Syllaby\Clonables\Clonable;
use Database\Factories\AvatarFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Avatar extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    const string STANDARD = 'standard';

    const string REAL_CLONE = 'real-clone';

    const string REAL_CLONE_LITE = 'real-clone-lite';

    const string PHOTO = 'photo';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Gets the active avatars only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Gets also the avatars owned by given user.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id)->orWhereNull('user_id');
    }

    /**
     * User that owns the avatar.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the avatar clone proof.
     */
    public function clone(): MorphOne
    {
        return $this->morphOne(Clonable::class, 'clonable');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('preview')->singleFile();
        $this->addMediaCollection('photo-avatar')->singleFile();
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'provider' => RealCloneProvider::class,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AvatarFactory::new();
    }
}
