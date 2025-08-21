<?php

namespace App\Syllaby\Assets;

use App\Syllaby\Characters\Genre;
use App\Syllaby\Users\User;
use Spatie\Image\Enums\Fit;
use Laravel\Scout\Searchable;
use App\Syllaby\Videos\Faceless;
use Spatie\MediaLibrary\HasMedia;
use Database\Factories\AssetFactory;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Bookmarks\Bookmarkable;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Asset extends Model implements HasMedia
{
    use Bookmarkable, HasFactory, InteractsWithMedia, Searchable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'status' => AssetStatus::class,
            'provider' => AssetProvider::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function scopePublic(Builder $builder): Builder
    {
        return $builder->where('is_private', false);
    }

    public function scopePrivate(Builder $builder): Builder
    {
        return $builder->where('is_private', true);
    }

    /**
     * Get only the assets available for the given user.
     */
    public function scopeOwnedBy(Builder $builder, User $user): Builder
    {
        return $builder->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)->orWhereNull('user_id');
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('sfx');
        $this->addMediaCollection('audios');
        $this->addMediaCollection('videos');
        $this->addMediaCollection('images');
        $this->addMediaCollection('default')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Crop, 720)
            ->extractVideoFrameAtSecond(4)
            ->performOnCollections(['default', 'videos']);

        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Crop, 720)
            ->performOnCollections('images');
    }

    public function videos(): MorphToMany
    {
        return $this->morphedByMany(Faceless::class, 'model', 'video_assets')
            ->using(VideoAsset::class)
            ->withPivot('id', 'uuid', 'order', 'active')
            ->withTimestamps();
    }

    #[SearchUsingFullText(['description'])]
    public function toSearchableArray(): array
    {
        return ['description' => $this->description];
    }

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }
}
