<?php

namespace App\Syllaby\Videos;

use App\Syllaby\Ideas\Idea;
use App\Syllaby\Users\User;
use Spatie\Image\Enums\Fit;
use App\Syllaby\Planner\Event;
use App\Syllaby\Folders\Resource;
use App\Syllaby\Loggers\VideoLog;
use Spatie\MediaLibrary\HasMedia;
use Database\Factories\VideoFactory;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Videos\Enums\VideoStatus;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Video extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    const string CUSTOM = 'custom';

    const string FACELESS = 'faceless';

    const string EDITED_FACELESS = 'edited-faceless';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'retries',
    ];

    /**
     * Get only the completed videos.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', VideoStatus::COMPLETED->value);
    }

    /**
     * Get only the videos for the given user.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('videos.user_id', $user->id);
    }

    /**
     * Get the Idea of the video
     */
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    /**
     * Whether the video has completed or failed rendering.
     */
    public function isFinished(): bool
    {
        return in_array($this->status->value, [
            VideoStatus::FAILED->value,
            VideoStatus::COMPLETED->value,
        ]);
    }

    /**
     * Whether an action is happening on the video.
     */
    public function isBusy(): bool
    {
        return in_array($this->status->value, [
            VideoStatus::SYNCING->value,
            VideoStatus::RENDERING->value,
        ]);
    }

    /**
     * Gets the video author.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video's event.
     */
    public function event(): MorphOne
    {
        return $this->morphOne(Event::class, 'model');
    }

    /**
     * Gets the video footage.
     */
    public function footage(): HasOne
    {
        return $this->hasOne(Footage::class);
    }

    /**
     * Gets the faceless video.
     */
    public function faceless(): HasOne
    {
        return $this->hasOne(Faceless::class);
    }

    /**
     * Gets the scheduler of the video.
     */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(Scheduler::class);
    }

    /**
     * Gets the clones of the video.
     */
    public function clones(): HasManyThrough
    {
        return $this->hasManyThrough(RealClone::class, Footage::class);
    }

    /**
     * Ges publications using the video.
     */
    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    /**
     * Gets log records for current video.
     */
    public function log(): HasOne
    {
        return $this->hasOne(VideoLog::class);
    }

    /**
     * Get the video's resources.
     */
    public function resource(): MorphOne
    {
        return $this->morphOne(Resource::class, 'model');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('assets');
        $this->addMediaCollection('video')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Max)
            ->extractVideoFrameAtSecond(4)
            ->performOnCollections('video');

        $this->addMediaConversion('preview')
            ->nonQueued()
            ->performOnCollections('assets');
    }

    /**
     * Get the casts for the model.
     */
    protected function casts(): array
    {
        return [
            'caption' => 'array',
            'metadata' => 'array',
            'synced_at' => 'datetime',
            'status' => VideoStatus::class,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return VideoFactory::new();
    }
}
