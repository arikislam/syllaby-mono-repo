<?php

namespace App\Syllaby\Assets;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class VideoAsset extends MorphPivot
{
    protected $table = 'video_assets';

    protected static function booted(): void
    {
        static::creating(function (VideoAsset $videoAsset) {
            $videoAsset->uuid = (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * Get the parent model (Faceless or any other video type).
     */
    public function model(): BelongsTo
    {
        return $this->morphTo();
    }

    /**
     * Get the associated asset.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
