<?php

namespace App\Syllaby\Speeches;

use App\Syllaby\Users\User;
use Spatie\MediaLibrary\HasMedia;
use App\Syllaby\RealClones\RealClone;
use Database\Factories\SpeechFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Syllaby\Speeches\Enums\SpeechStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Speech extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'synced' => 'boolean',
            'is_custom' => 'boolean',
            'synced_at' => 'datetime',
            'status' => SpeechStatus::class,
        ];
    }

    /**
     * Gets the script from real clone.
     */
    public function realClone(): BelongsTo
    {
        return $this->belongsTo(RealClone::class);
    }

    /**
     * Gets the speech owner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('script')->singleFile();
    }

    /**
     * Interact with the speech's hash.
     */
    protected function hash(): Attribute
    {
        return Attribute::make(
            set: fn($value) => md5(serialize([$this->voice_id, $this->provider, $this->provider_id]))
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return SpeechFactory::new();
    }
}
