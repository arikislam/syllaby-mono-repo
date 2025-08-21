<?php

namespace App\Syllaby\Schedulers\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use App\Syllaby\Schedulers\Enums\SchedulerSource;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use App\Syllaby\Schedulers\Concerns\HandlesRecurrenceRules;

class UpdateSchedulerAction
{
    use HandlesRecurrenceRules {
        build as buildRrules;
    }

    /**
     * The recurrence rules.
     */
    private array $rrules = [];

    /**
     * Update the scheduler with the given data.
     */
    public function handle(Scheduler $scheduler, array $input): Scheduler
    {
        DB::transaction(function () use ($scheduler, $input) {
            $scheduler = $scheduler->fill([
                'source' => SchedulerSource::AI,
                'status' => SchedulerStatus::REVIEWING,
                'character_id' => Arr::get($input, 'character_id', $scheduler->character_id),
                'title' => Arr::get($input, 'topic', $scheduler->topic),
                'topic' => Arr::get($input, 'topic', $scheduler->topic),
                'metadata' => $this->metadata($scheduler->metadata, $input),
            ]);

            if ($this->shouldRebuildRrules($scheduler, $input)) {
                $scheduler->rrules = $this->buildRrules($input);
                $scheduler->status = SchedulerStatus::DRAFT;
                app(FlushOccurrencesAction::class)->handle($scheduler);
            }

            if (Arr::has($input, 'csv')) {
                $scheduler->source = SchedulerSource::CSV;
                $scheduler->status = SchedulerStatus::REVIEWING;
                $this->bulk($scheduler, Arr::get($input, 'csv', []));
            }

            $scheduler->save();
            $scheduler->channels()->sync(Arr::get($input, 'social_channels'));
        });

        return $scheduler->refresh();
    }

    /**
     * Determine if the recurrence rules should be rebuilt.
     */
    private function shouldRebuildRrules(Scheduler $scheduler, array $input): bool
    {
        $current = json_encode($scheduler->rrules);

        $intent = json_encode($this->build($input));

        return md5($current) !== md5($intent);
    }

    /**
     * Get the metadata for the scheduler.
     */
    private function metadata(?array $metadata = [], array $input = []): array
    {
        $labels = Arr::get($metadata, 'ai_labels', false);
        $labels = Arr::get($input, 'ai_labels', $labels);

        $description = Arr::get($metadata, 'custom_description');
        $description = Arr::get($input, 'custom_description', $description);

        return array_merge($metadata, [
            'ai_labels' => $labels,
            'custom_description' => $description,
        ]);
    }

    /**
     * Bulk the scheduler.
     */
    private function bulk(Scheduler $scheduler, array $csv): void
    {
        if (blank($csv)) {
            return;
        }

        $rdates = $scheduler->rdates();

        $occurrences = collect($csv)->map(fn ($row, $index) => [
            'occurs_at' => Arr::get($rdates, $index),
            'script' => Arr::get($row, 'script'),
            'topic' => Arr::get($row, 'title'),
            'user_id' => $scheduler->user_id,
            'scheduler_id' => $scheduler->id,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Occurrence::insert($occurrences->toArray());
    }
}
