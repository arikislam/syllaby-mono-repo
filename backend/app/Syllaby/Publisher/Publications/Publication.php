<?php

namespace App\Syllaby\Publisher\Publications;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use InvalidArgumentException;
use App\Syllaby\Planner\Event;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\PublicationFactory;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Metrics\Traits\HasMetricAggregates;

class Publication extends Model implements HasMedia
{
    use HasFactory;
    use HasMetricAggregates;
    use InteractsWithMedia;

    protected $table = 'publications';

    protected $fillable = [
        'user_id',
        'video_id',
        'name',
        'draft',
        'temporary',
        'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'draft' => 'boolean',
            'temporary' => 'boolean',
            'scheduled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(SocialChannel::class, 'account_publications')
            ->using(AccountPublication::class)
            ->withPivot('status', 'post_type', 'metadata', 'error_message', 'provider_media_id')
            ->withTimestamps();
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(PublicationMetricValue::class);
    }

    public function event(): MorphOne
    {
        return $this->morphOne(Event::class, 'model');
    }

    public function asset(): ?Media
    {
        if (! $this->video_id) {
            return $this->getFirstMedia('publications');
        }

        return $this->video->getFirstMedia('video');
    }

    public function thumbnail(SocialAccountEnum $provider): ?Media
    {
        return match ($provider) {
            SocialAccountEnum::Youtube => $this->getFirstMedia('youtube-thumbnail'),
            SocialAccountEnum::LinkedIn => $this->getFirstMedia('linkedin-thumbnail'),
            SocialAccountEnum::Facebook => $this->getFirstMedia('facebook-thumbnail'),
            default => throw new InvalidArgumentException('Invalid provider')
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('publications')->singleFile();
        $this->addMediaCollection('youtube-thumbnail')->singleFile();
        $this->addMediaCollection('linkedin-thumbnail')->singleFile();
        $this->addMediaCollection('facebook-thumbnail')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(1920)
            ->height(1080)
            ->sharpen(10)
            ->performOnCollections('publications');
    }

    protected static function newFactory(): Factory
    {
        return PublicationFactory::new();
    }
}
