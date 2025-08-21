<?php

namespace App\Syllaby\Schedulers\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Trackers\Tracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Videos\DTOs\Options;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Videos\Enums\VideoProvider;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Schedulers\Enums\SchedulerSource;
use App\Syllaby\Videos\Actions\DeleteVideoAction;
use Illuminate\Database\Eloquent\Relations\Relation;

class BulkCreateSchedulerVideosAction
{
    /**
     * Handle the action.
     */
    public function handle(Collection $occurrences, Scheduler $scheduler, User $user, array $input): Collection
    {
        $this->reset($scheduler);

        return DB::transaction(function () use ($occurrences, $scheduler, $user, $input) {
            $videos = $this->videos($occurrences, $scheduler, $user);
            $faceless = $this->faceless($videos, $occurrences, $scheduler, $user, $input);
            $this->trackers($faceless);

            return $videos->loadMissing('faceless');
        });
    }

    /**
     * Delete previously created video records.
     */
    private function reset(Scheduler $scheduler): void
    {
        if ($scheduler->videos()->count() === 0) {
            return;
        }

        try {
            $scheduler->load('videos');
            $scheduler->videos->each(fn ($video) => app(DeleteVideoAction::class)->handle($video));
        } catch (Exception $exception) {
            Log::error('Unable to clean unfinished videos for scheduler [{id}] due to: {message}.', [
                'id' => $scheduler->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Create the videos.
     */
    private function videos(Collection $occurrences, Scheduler $scheduler, User $user): Collection
    {
        $attributes = $occurrences->map(fn ($occurrence) => [
            'user_id' => $user->id,
            'title' => $occurrence->topic,
            'type' => $scheduler->type,
            'status' => VideoStatus::RENDERING,
            'scheduler_id' => $scheduler->id,
            'provider' => VideoProvider::CREATOMATE->value,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        Video::insert($attributes);

        return Video::where('user_id', $user->id)->where('scheduler_id', $scheduler->id)->get();
    }

    /**
     * Create the faceless.
     */
    private function faceless(Collection $videos, Collection $occurrences, Scheduler $scheduler, User $user, array $input): Collection
    {
        $wpm = $this->resolveWpm($scheduler);

        $attributes = $videos->map(function ($video, $index) use ($scheduler, $user, $input, $occurrences, $wpm) {
            $occurrence = $occurrences->get($index);

            return [
                'user_id' => $user->id,
                'video_id' => $video->id,
                'genre_id' => $scheduler->options->genre,
                'character_id' => $scheduler->character_id,
                'voice_id' => $scheduler->options->voice_id,
                'music_id' => $scheduler->options->music_id,
                'type' => $this->resolveFacelessType($input),
                'script' => $occurrence->script,
                'options' => $this->options($scheduler)->toJson(),
                'estimated_duration' => $this->resolveEstimatedDuration($scheduler, $occurrence, $wpm, $input),
                'background_id' => $scheduler->options->background_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        Faceless::insert($attributes);

        return Faceless::where('user_id', $user->id)->whereIn('video_id', $videos->pluck('id'))->get();
    }

    /**
     * Create the trackers.
     */
    private function trackers(Collection $faceless): void
    {
        $attributes = $faceless->map(fn ($faceless) => [
            'count' => 0,
            'limit' => 3,
            'trackable_id' => $faceless->id,
            'trackable_type' => Relation::getMorphAlias(Faceless::class),
            'name' => 'image-generation',
            'user_id' => $faceless->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        Tracker::insert($attributes);
    }

    /**
     * Create the faceless options object.
     */
    private function options(Scheduler $scheduler): Options
    {
        return new Options(
            font_family: $scheduler->options->captions->font_family,
            position: $scheduler->options->captions->position,
            aspect_ratio: $scheduler->options->aspect_ratio,
            sfx: $scheduler->options->sfx,
            font_color: $scheduler->options->captions->font_color,
            volume: $scheduler->options->music_volume,
            transition: $scheduler->options->transition,
            caption_effect: $scheduler->options->captions->effect,
            overlay: $scheduler->options->overlay,
        );
    }

    /**
     * Resolve the faceless type.
     */
    private function resolveFacelessType(array $input): string
    {
        return FacelessType::tryFrom(Arr::get($input, 'options.type'))->value;
    }

    /**
     * Resolve the estimated duration for csv imported scripts.
     */
    private function resolveEstimatedDuration(Scheduler $scheduler, Occurrence $occurrence, ?int $wpm, array $input): int
    {
        if ($scheduler->source !== SchedulerSource::CSV) {
            return Arr::get($input, 'options.duration', $scheduler->options->duration);
        }

        $duration = reading_time($occurrence->script, $wpm);

        return match (true) {
            $duration <= 60 => 60,
            $duration <= 180 => 180,
            $duration <= 300 => 300,
            default => 600
        };
    }

    /**
     * Resolve the words per minute.
     */
    private function resolveWpm(Scheduler $scheduler): ?int
    {
        if ($scheduler->source === SchedulerSource::CSV) {
            return Voice::select('words_per_minute')->find($scheduler->options->voice_id)->words_per_minute;
        }

        return null;
    }
}
