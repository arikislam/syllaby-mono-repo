<?php

namespace App\Syllaby\Videos;

use App\Syllaby\Characters\Genre;
use App\Syllaby\Characters\Character;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Metadata\Caption;
use App\Syllaby\Trackers\Tracker;
use Spatie\MediaLibrary\HasMedia;
use App\Syllaby\Assets\VideoAsset;
use App\Syllaby\Metadata\Timeline;
use App\Syllaby\Generators\Generator;
use Database\Factories\FacelessFactory;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Videos\Casts\OptionsCast;
use App\Syllaby\Videos\Enums\FacelessType;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Faceless extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    const string AI_VISUALS = 'ai-visuals';

    const string SINGLE_CLIP = 'single-clip';

    const string B_ROLL = 'b-roll';

    const string URL_BASED = 'url-based';

    protected $guarded = [];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['length'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Faceless $faceless) {
            $faceless->hash = $faceless->hashed();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function voice(): BelongsTo
    {
        return $this->belongsTo(Voice::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    public function music(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function trackers(): MorphMany
    {
        return $this->morphMany(Tracker::class, 'trackable');
    }

    public function generator(): MorphOne
    {
        return $this->morphOne(Generator::class, 'model');
    }

    public function speech(): ?string
    {
        return $this->getFirstMediaUrl('script');
    }

    public function background(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'background_id');
    }

    public function assets(): MorphToMany
    {
        return $this->morphToMany(Asset::class, 'model', 'video_assets')
            ->using(VideoAsset::class)
            ->withPivot('id', 'uuid', 'order', 'active')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Get the timeline for the faceless video.
     */
    public function timeline(): MorphOne
    {
        return $this->morphOne(Timeline::class, 'model');
    }

    /**
     * Get the caption for the faceless video.
     */
    public function captions(): MorphOne
    {
        return $this->morphOne(Caption::class, 'model');
    }

    /**
     * Get the watermark for the faceless video.
     */
    public function watermark(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'watermark_id');
    }

    public function hasPendingModifications(): bool
    {
        return $this->assets()->where('status', AssetStatus::PROCESSING)->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('script'); // Storing the whole voice-over.
    }

    public function hashOptions(mixed $options): string
    {
        return md5(serialize($options));
    }

    public function hashSpeech(?string $script = null, ?int $voice = null): string
    {
        $script ??= $this->script;
        $voice ??= $this->voice_id;

        return md5(serialize([$script, $voice]));
    }

    protected function hashed(): array
    {
        return [
            'options' => $this->hashOptions($this->options),
            'speech' => $this->hashSpeech($this->script, $this->voice_id),
        ];
    }

    public function length(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->estimated_duration <= 60 ? 'short' : 'long'
        );
    }

    public function isExported(): bool
    {
        return $this->video->exports > 0;
    }

    /**
     * Get the casts for the model.
     */
    protected function casts(): array
    {
        return [
            'hash' => 'array',
            'is_transcribed' => 'boolean',
            'type' => FacelessType::class,
            'options' => OptionsCast::class,
            'estimated_duration' => 'integer',
        ];
    }

    /**
     * Get the factory for the model.
     */
    protected static function newFactory(): Factory
    {
        return FacelessFactory::new();
    }
}
