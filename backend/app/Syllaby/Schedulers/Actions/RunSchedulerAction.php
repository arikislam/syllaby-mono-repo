<?php

namespace App\Syllaby\Schedulers\Actions;

use Exception;
use RRule\RRule;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Asset;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\DTOs\Options;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use App\Syllaby\Schedulers\Jobs\MoveSchedulerVideos;
use App\Syllaby\Credits\Jobs\ProcessFacelessVideoCharge;
use App\Syllaby\Schedulers\Jobs\HandleSchedulerCompletion;
use App\Syllaby\Videos\Jobs\Faceless\FindStockFootageClips;
use App\Syllaby\Schedulers\Jobs\CreateSchedulerPublications;
use App\Syllaby\Videos\Jobs\Faceless\TriggerMediaGeneration;
use App\Syllaby\Videos\Jobs\Faceless\BuildFacelessVideoSource;
use App\Syllaby\Videos\Jobs\Faceless\TransloadBackgroundVideo;
use App\Syllaby\Videos\Jobs\Faceless\GenerateFacelessVoiceOver;
use App\Syllaby\Videos\Jobs\Faceless\ExtractStockFootageKeywords;

class RunSchedulerAction
{
    public function __construct(private readonly BulkCreateSchedulerVideosAction $bulk) {}

    /**
     * Handle the start of a scheduler.
     */
    public function handle(Scheduler $scheduler, User $user, array $input): Scheduler
    {
        $options = Options::fromRequest(Arr::only($input, ['options', 'captions']));

        $scheduler = $scheduler->fill([
            'options' => $options,
            'status' => SchedulerStatus::GENERATING,
            'character_id' => Arr::get($input, 'options.character_id'),
            'metadata->destination' => Arr::get($input, 'destination_id'),
        ]);

        $scheduler = tap($this->ensureFutureDates($scheduler))->save();

        $scheduler->loadMissing('occurrences');
        if (! $videos = $this->bulk->handle($scheduler->occurrences, $scheduler, $user, $input)) {
            return tap($scheduler)->update(['status' => SchedulerStatus::REVIEWING]);
        }

        Bus::batch([
            ...$this->chainOfSteps($videos, $user, $input),
            new MoveSchedulerVideos($scheduler),
        ])->finally(function () use ($scheduler) {
            dispatch(new HandleSchedulerCompletion($scheduler));
        })->dispatch();

        return $scheduler;
    }

    /**
     * Ensures that the scheduler has future dates.
     */
    private function ensureFutureDates(Scheduler $scheduler): Scheduler
    {
        $dates = collect($scheduler->rdates());

        if ($dates->first()->isFuture()) {
            return $scheduler;
        }

        $start = $dates->first()->copy()->startOfDay();
        $end = $dates->last()->copy()->endOfDay();

        $days = ceil($start->diffInDays($end));
        $hours = $dates->map(fn ($date) => $date->format('H:i'))->unique()->values();

        $scheduler->rrules = $hours->map(fn ($time) => (new RRule([
            'INTERVAL' => 1,
            'FREQ' => RRule::DAILY,
            'COUNT' => $days,
            'DTSTART' => now()->addDay()->setTimeFrom($time),
        ]))->rfcString())->toArray();

        $rdates = array_reverse($scheduler->rdates());

        $scheduler->occurrences()->latest('occurs_at')->get()->each(function ($occurrence, $index) use ($rdates) {
            $occurrence->update(['occurs_at' => $rdates[$index]->format('Y-m-d H:i:s')]);
        });

        return $scheduler;
    }

    /**
     * Creates a chained batch jobs to generate the videos.
     *
     * @throws Exception
     */
    private function chainOfSteps(Collection $videos, User $user, array $input): array
    {
        return match (true) {
            filled(Arr::get($input, 'options.genre_id')) => $this->buildAiVisualsVideo($videos, $user),
            filled(Arr::get($input, 'options.background_id')) => $this->buildSingleClipVideo($videos, $user, $input),
            default => $this->buildBRollVideo($videos, $user),
        };
    }

    /**
     * Builds the jobs to generate the videos with a genre.
     */
    private function buildAiVisualsVideo(Collection $videos, User $user): array
    {
        return $videos->map(fn ($video, $index) => [
            new GenerateFacelessVoiceOver($video->faceless),
            new ProcessFacelessVideoCharge($video->faceless),
            new TriggerMediaGeneration($video->faceless),
            new CreateSchedulerPublications($user, $video, $index),
        ])->toArray();
    }

    /**
     * Builds the jobs to generate the videos with a background.
     */
    private function buildBRollVideo(Collection $videos, User $user): array
    {
        return $videos->map(fn ($video, $index) => [
            new GenerateFacelessVoiceOver($video->faceless),
            new ProcessFacelessVideoCharge($video->faceless),
            new ExtractStockFootageKeywords($video->faceless),
            new FindStockFootageClips($video->faceless),
            new BuildFacelessVideoSource($video->faceless),
            new CreateSchedulerPublications($user, $video, $index),
        ])->toArray();
    }

    /**
     * Builds the jobs to generate the videos with a background.
     */
    private function buildSingleClipVideo(Collection $videos, User $user, array $input): array
    {
        $asset = Asset::query()->find(Arr::get($input, 'options.background_id'));

        return $videos->map(fn ($video, $index) => [
            new TransloadBackgroundVideo($video->faceless, $asset),
            new GenerateFacelessVoiceOver($video->faceless),
            new ProcessFacelessVideoCharge($video->faceless),
            new BuildFacelessVideoSource($video->faceless),
            new CreateSchedulerPublications($user, $video, $index),
        ])->toArray();
    }
}
