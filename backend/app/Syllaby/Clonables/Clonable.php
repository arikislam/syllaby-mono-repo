<?php

namespace App\Syllaby\Clonables;

use App\Syllaby\Users\User;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Subscriptions\Purchase;
use Database\Factories\ClonableFactory;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Syllaby\Clonables\Enums\CloneStatus;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Clonable extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => CloneStatus::class,
        ];
    }

    /**
     * Get the owner of the clone.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related purchase record.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the parent clonable model (avatar or voice).
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Media Library collection registration.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')->onlyKeepLatest(3);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ClonableFactory::new();
    }
}
