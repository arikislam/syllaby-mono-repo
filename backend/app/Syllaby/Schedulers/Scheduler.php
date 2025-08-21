<?php

namespace App\Syllaby\Schedulers;

use RRule\RSet;
use Carbon\Carbon;
use App\Syllaby\Ideas\Idea;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use App\Syllaby\Planner\Event;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Characters\Character;
use App\Syllaby\Generators\Generator;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\SchedulerFactory;
use App\Syllaby\Schedulers\Casts\OptionsCast;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Schedulers\Enums\SchedulerSource;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scheduler extends Model
{
    use HasFactory;

    const string REMINDER_KEY = 'reminders:scheduled-events';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Scope query to only include publishing schedulers.
     */
    public function scopePublishing($query)
    {
        return $query->where('status', SchedulerStatus::PUBLISHING);
    }

    /**
     * Scheduler creator.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Selected voice to use in the speech.
     */
    public function voice(): BelongsTo
    {
        return $this->belongsTo(Voice::class, 'voice_id');
    }

    /**
     * Selected voice to use in the speech.
     */
    public function music(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'music_id');
    }

    /**
     * Scheduler's original idea.
     */
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    /**
     * Scheduler's character.
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Scheduler's social accounts publication targets.
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(SocialChannel::class)->withTimestamps();
    }

    /**
     * Scheduler's videos.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Scheduler's calendar events.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the occurrences details associated with the scheduler.
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }

    /**
     * Get the AI generator associated with the scheduler.
     */
    public function generator(): MorphOne
    {
        return $this->morphOne(Generator::class, 'model');
    }

    /**
     * Get the recurrence dates for the scheduler.
     *
     * @return array<Carbon>
     */
    public function rdates(): array
    {
        if (empty($this->rrules)) {
            return [];
        }

        $rset = new RSet;

        Arr::map($this->rrules, fn ($rule) => $rset->addRRule($rule));

        return Arr::map($rset->getOccurrences(), fn ($occurrence) => Carbon::parse($occurrence));
    }

    /**
     * Check if the scheduler is paused.
     */
    public function isPaused(): bool
    {
        return filled($this->paused_at);
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'rrules' => 'array',
            'metadata' => 'array',
            'paused_at' => 'datetime',
            'options' => OptionsCast::class,
            'source' => SchedulerSource::class,
            'status' => SchedulerStatus::class,
        ];
    }

    /**
     * Get the voice id from the details array.
     */
    protected function voiceId(): Attribute
    {
        return Attribute::make(get: function (mixed $value, array $attributes) {
            return Arr::get(json_decode($attributes['options'], true), 'voice_id', null);
        });
    }

    /**
     * Get the voice id from the details array.
     */
    protected function musicId(): Attribute
    {
        return Attribute::make(get: function (mixed $value, array $attributes) {
            return Arr::get(json_decode($attributes['options'], true), 'music_id', null);
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return SchedulerFactory::new();
    }
}
