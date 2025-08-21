<?php

namespace App\Syllaby\Presets;

use App\Syllaby\Tags\Tag;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Folders\Resource;
use App\Syllaby\Characters\Genre;
use Database\Factories\FacelessPresetFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacelessPreset extends Preset
{
    use HasFactory;

    /**
     * Get the music associated with the preset.
     */
    public function music(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'music_id');
    }

    /**
     * Get the voice associated with the preset.
     */
    public function voice(): BelongsTo
    {
        return $this->belongsTo(Voice::class);
    }

    /**
     * Get the background associated with the preset.
     */
    public function background(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'background_id');
    }

    /**
     * Get the watermark associated with the preset.
     */
    public function watermark(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'watermark_id');
    }

    /**
     * Get the folder associated with the preset.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    /**
     * Get the music category associated with the preset.
     */
    public function music_category(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'music_category_id', 'id');
    }

    /**
     * Get the genre associated with the preset.
     */
    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    /**
     * Get the type of the preset.
     */
    public function type(): Attribute
    {
        return Attribute::make(get: fn () => 'faceless');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return FacelessPresetFactory::new();
    }
}
