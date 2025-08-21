<?php

namespace App\Syllaby\Schedulers\Actions;

use RRule\RRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\System\Enums\QueueType;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\DTOs\Options;
use App\Syllaby\Schedulers\Enums\SchedulerSource;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use App\Syllaby\Schedulers\Jobs\ExpandSchedulerTopic;
use App\Syllaby\Schedulers\Jobs\BulkCreateOccurrences;
use App\Syllaby\Schedulers\Jobs\GenerateOccurrenceScript;

class CreateOccurrenceAction
{
    /**
     * Handle the creation of scripts for a scheduler.
     */
    public function handle(Scheduler $scheduler, array $input): Scheduler
    {
        $scheduler = tap($scheduler)->update([
            'topic' => Arr::get($input, 'topic'),
            'source' => SchedulerSource::AI,
            'status' => SchedulerStatus::WRITING,
            'options' => $this->options($input),
            'title' => $this->resolveTitle($scheduler, $input),
        ]);

        $scheduler->generator()->updateOrCreate([], [
            'topic' => Arr::get($input, 'topic'),
            'tone' => Arr::get($input, 'tone', 'Formal'),
            'length' => Arr::get($input, 'duration', 60),
            'style' => Arr::get($input, 'style', 'Narrative'),
            'language' => Arr::get($input, 'language', 'English'),
        ]);

        $dates = $this->ensureFutureDates($scheduler);

        app(FlushOccurrencesAction::class)->handle($scheduler);

        Bus::chain([
            new ExpandSchedulerTopic($scheduler, count($dates)),
            new BulkCreateOccurrences($scheduler, $dates),
            $this->buildScriptsBatch($scheduler, $dates),
        ])->dispatch();

        return $scheduler;
    }

    /**
     * Builds a batch of jobs to generate scripts for each occurrence.
     */
    protected function buildScriptsBatch(Scheduler $scheduler, array $dates): PendingBatch
    {
        $jobs = Arr::map($dates, fn ($date) => new GenerateOccurrenceScript($scheduler, $date));

        return Bus::batch($jobs)
            ->then(fn () => $scheduler->update(['status' => SchedulerStatus::REVIEWING]))
            ->onConnection('videos')->onQueue(QueueType::RENDER->value);
    }

    /**
     * Builds the options for a scheduler.
     */
    protected function options(array $input): Options
    {
        return Options::fromRequest([
            'options' => Arr::only($input, ['language', 'duration']),
        ]);
    }

    /**
     * Ensures that the dates are in the future.
     */
    protected function ensureFutureDates(Scheduler $scheduler): array
    {
        $dates = $scheduler->rdates();

        if (head($dates)->isFuture()) {
            return $dates;
        }

        $start = head($dates)->copy()->startOfDay();
        $end = last($dates)->copy()->endOfDay();

        $days = ceil($start->diffInDays($end));
        $hours = collect($dates)->map(fn ($date) => $date->format('H:i'))->unique()->values();

        $scheduler->rrules = $hours->map(fn ($time) => (new RRule([
            'INTERVAL' => 1,
            'FREQ' => RRule::DAILY,
            'COUNT' => $days,
            'DTSTART' => now()->addDay()->setTimeFrom($time),
        ]))->rfcString())->toArray();

        $scheduler->save();

        return $scheduler->rdates();
    }

    /**
     * Resolves the title for a scheduler.
     */
    protected function resolveTitle(Scheduler $scheduler, array $input): string
    {
        if (Str::startsWith($scheduler->title, 'Bulk Scheduler -')) {
            return Arr::get($input, 'topic');
        }

        return $scheduler->title;
    }
}
