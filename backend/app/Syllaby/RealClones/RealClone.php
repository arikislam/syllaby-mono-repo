<?php

namespace App\Syllaby\RealClones;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Spatie\Image\Enums\Fit;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Speeches\Speech;
use Spatie\MediaLibrary\HasMedia;
use App\Syllaby\Generators\Generator;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\RealCloneFactory;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RealClone extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hash' => 'array',
            'synced_at' => 'datetime',
            'status' => RealCloneStatus::class,
        ];
    }

    public function footage(): BelongsTo
    {
        return $this->belongsTo(Footage::class);
    }

    /**
     * Get the Real Clone's generator.
     */
    public function generator(): MorphOne
    {
        return $this->morphOne(Generator::class, 'model');
    }

    /**
     * Get the real clone owner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the used avatar to generate the real clone.
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class);
    }

    /**
     * Get the used voice to generate the real clone.
     */
    public function voice(): BelongsTo
    {
        return $this->belongsTo(Voice::class);
    }

    /**
     * Get the current used speech.
     */
    public function speech(): HasOne
    {
        return $this->hasOne(Speech::class);
    }

    /**
     * Checks if the video either completed, failed or in draft.
     */
    public function isFinished(): bool
    {
        return !in_array($this->status->value, [
            RealCloneStatus::SYNCING->value,
            RealCloneStatus::GENERATING->value,
        ]);
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('video')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Contain, 720)
            ->extractVideoFrameAtSecond(4)
            ->performOnCollections('video');
    }

    /**
     * Interact with the real clone's hash.
     */
    public function hashes(?string $key = null): string|array
    {
        $hashes = [
            'speech' => md5(serialize([$this->voice_id, $this->script])),
            'real-clone' => md5(serialize([$this->voice_id, $this->avatar_id, $this->script])),
        ];

        return blank($key) ? $hashes : Arr::get($hashes, $key);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return RealCloneFactory::new();
    }
}
